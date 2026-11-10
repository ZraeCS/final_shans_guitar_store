<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Repository\CustomerOrderRepository;
use App\Service\OrderWorkflow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Orders page + the actions behind every order button
 * (confirm, ship, deliver, cancel, verify GCash).
 */
#[Route('/dashboard/orders')]
#[IsGranted('ROLE_STAFF')]
final class OrderController extends AbstractController
{
    public function __construct(private readonly OrderWorkflow $workflow)
    {
    }

    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(Request $request, CustomerOrderRepository $orderRepository): Response
    {
        $status = OrderStatus::tryFrom($request->query->getString('status'));
        $query = $request->query->getString('q');

        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->search($status, $query),
            'counts' => $orderRepository->countPerStatus(),
            'statuses' => OrderStatus::cases(),
            'current_status' => $status,
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CustomerOrder $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
            'next_statuses' => $this->workflow->nextStatuses($order),
        ]);
    }

    /** Confirm / Mark shipped / Mark delivered / Cancel buttons. */
    #[Route('/{id}/status', name: 'app_order_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeStatus(Request $request, CustomerOrder $order): Response
    {
        if (!$this->isCsrfTokenValid('order'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'That button expired. Please try again.');

            return $this->goBack($request, $order);
        }

        $status = OrderStatus::tryFrom($request->getPayload()->getString('status'));

        try {
            if ($status === null) {
                throw new \DomainException('Unknown status.');
            }
            $this->workflow->changeStatus($order, $status);
            $this->addFlash('success', match ($status) {
                OrderStatus::Cancelled => sprintf('Order #%d was cancelled. Its items are back in stock.', $order->getId()),
                default => sprintf('Order #%d is now %s.', $order->getId(), strtolower($status->label())),
            });
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->goBack($request, $order);
    }

    /** "Verify GCash": the admin saw the payment arrive in the GCash app. */
    #[Route('/{id}/verify-gcash', name: 'app_order_verify_gcash', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function verifyGcash(Request $request, CustomerOrder $order): Response
    {
        if (!$this->isCsrfTokenValid('order'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'That button expired. Please try again.');

            return $this->goBack($request, $order);
        }

        /** @var User $admin */
        $admin = $this->getUser();

        try {
            $this->workflow->verifyGcash($order, $admin);
            $this->addFlash('success', sprintf('GCash payment for order #%d verified. The order is now confirmed.', $order->getId()));
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->goBack($request, $order);
    }

    /**
     * After a button, return to the page it was pressed on
     * (each form sends a hidden "back" field: dashboard, orders, gcash or show).
     */
    private function goBack(Request $request, CustomerOrder $order): RedirectResponse
    {
        $status = $request->getPayload()->getString('back_status');

        return match ($request->getPayload()->getString('back')) {
            'dashboard' => $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER),
            'gcash' => $this->redirectToRoute('app_gcash_index', [], Response::HTTP_SEE_OTHER),
            'orders' => $this->redirectToRoute('app_order_index', array_filter(['status' => $status]), Response::HTTP_SEE_OTHER),
            default => $this->redirectToRoute('app_order_show', ['id' => $order->getId()], Response::HTTP_SEE_OTHER),
        };
    }
}
