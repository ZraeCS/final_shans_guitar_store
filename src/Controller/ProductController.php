<?php

namespace App\Controller;

use App\Entity\Guitar;
use App\Form\GuitarType;
use App\Repository\GuitarRepository;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Products & Stock page.
 * Every URL starts with /dashboard, so security.yaml already protects it.
 */
#[Route('/dashboard/products')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(env: 'STORE_DIR')]
        private readonly string $storeDir,
    ) {
    }

    /**
     * The list + the "Add product" form in a popup (the professor's
     * Form Manipulation lesson: the index page also handles the create form).
     */
    #[Route(name: 'app_product_index', methods: ['GET', 'POST'])]
    public function index(Request $request, GuitarRepository $guitarRepository): Response
    {
        $guitar = (new Guitar())->setItemCode($guitarRepository->suggestNextItemCode());
        $form = $this->createForm(GuitarType::class, $guitar, [
            'action' => $this->generateUrl('app_product_index'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->saveUploadedImage($form, $guitar)) {
            $this->entityManager->persist($guitar);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('%s was added.', $guitar->getName()));

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        $query = $request->query->getString('q');
        $category = $request->query->getString('category');

        return $this->render('product/index.html.twig', [
            'guitars' => $guitarRepository->search($query, $category),
            'units_in_stock' => $guitarRepository->countUnitsInStock(),
            'total_products' => $guitarRepository->count([]),
            'categories' => Guitar::CATEGORIES,
            'query' => $query,
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Guitar $guitar): Response
    {
        $form = $this->createForm(GuitarType::class, $guitar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->saveUploadedImage($form, $guitar)) {
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('%s was updated.', $guitar->getName()));

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'guitar' => $guitar,
            'form' => $form,
        ]);
    }

    /** The − and + buttons next to each stock number. */
    #[Route('/{id}/stock', name: 'app_product_stock', methods: ['POST'])]
    public function adjustStock(Request $request, Guitar $guitar): Response
    {
        if ($this->isCsrfTokenValid('stock'.$guitar->getId(), $request->getPayload()->getString('_token'))) {
            $change = $request->getPayload()->getInt('change');
            if (in_array($change, [-1, 1], true)) {
                $guitar->setStock($guitar->getStock() + $change); // setStock() never goes below 0
                $this->entityManager->flush();
            }
        }

        return $this->redirectToRoute('app_product_index', $this->listFilters($request), Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Guitar $guitar, OrderItemRepository $orderItemRepository): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$guitar->getId(), $request->getPayload()->getString('_token'))) {
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        // A product that appears in past orders must stay, or those orders
        // would lose their line items. (The database also blocks it: RESTRICT.)
        $timesOrdered = $orderItemRepository->countForGuitar($guitar);
        if ($timesOrdered > 0) {
            $this->addFlash('error', sprintf(
                '%s can’t be deleted: it appears in %d order%s. Set its stock to 0 or its availability to “Coming soon” instead.',
                $guitar->getName(), $timesOrdered, $timesOrdered === 1 ? '' : 's'
            ));

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        $name = $guitar->getName();
        $this->entityManager->remove($guitar);
        $this->entityManager->flush();
        $this->addFlash('success', sprintf('%s was deleted.', $name));

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Moves an uploaded photo into the STORE's uploads/guitars/ folder
     * (named like the store does: gtr_<random>.jpg) so the shop can show it too.
     * Returns false and adds a form error if the file couldn't be saved.
     */
    private function saveUploadedImage(FormInterface $form, Guitar $guitar): bool
    {
        /** @var UploadedFile|null $file */
        $file = $form->get('imageFile')->getData();
        if ($file === null) {
            return true; // no new photo: keep the current one
        }

        $fileName = 'gtr_'.bin2hex(random_bytes(6)).'.'.($file->guessExtension() ?? 'jpg');

        try {
            $file->move(rtrim($this->storeDir, '/\\').'/uploads/guitars', $fileName);
        } catch (FileException) {
            $form->get('imageFile')->addError(new \Symfony\Component\Form\FormError(
                'The photo couldn’t be saved. Check that STORE_DIR in config/services.yaml points to the store folder.'
            ));

            return false;
        }

        $guitar->setImage('uploads/guitars/'.$fileName);

        return true;
    }

    /** Keeps the search and category filter after pressing − or +. */
    private function listFilters(Request $request): array
    {
        return array_filter([
            'q' => $request->getPayload()->getString('q'),
            'category' => $request->getPayload()->getString('category'),
        ]);
    }
}
