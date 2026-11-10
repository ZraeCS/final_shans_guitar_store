<?php

namespace App\Controller;

use App\Service\SalesReport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Sales Reports: revenue, orders, best sellers and payment methods
 * for a chosen period. The adding-up happens in App\Service\SalesReport.
 */
#[Route('/dashboard/reports')]
#[IsGranted('ROLE_ADMIN')]
final class ReportController extends AbstractController
{
    public function __construct(private readonly SalesReport $salesReport)
    {
    }

    #[Route(name: 'app_report_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $period = $this->period($request);

        return $this->render('report/index.html.twig', [
            'period' => $period,
            'report' => $this->salesReport->build($period['from'], $period['to']),
            'ranges' => SalesReport::RANGES,
            'from_input' => $period['from']->format('Y-m-d'),
            'to_input' => $period['to']->modify('-1 day')->format('Y-m-d'),
        ]);
    }

    /** The same report as a spreadsheet (opens in Excel / Google Sheets). */
    #[Route('/export.csv', name: 'app_report_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $period = $this->period($request);
        $report = $this->salesReport->build($period['from'], $period['to']);

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // lets Excel read the ₱ sign and accents correctly
        fputcsv($out, ["Shan's Guitar sales report", $period['label']], escape: '');
        fputcsv($out, [], escape: '');
        fputcsv($out, [ucfirst($report['period_unit']), 'Orders', 'Revenue (PHP)'], escape: '');
        foreach ($report['by_period'] as $row) {
            fputcsv($out, [$row['label'], $row['orders'], number_format($row['revenue'], 2, '.', '')], escape: '');
        }
        fputcsv($out, ['Total', $report['sales'], number_format($report['revenue'], 2, '.', '')], escape: '');
        fputcsv($out, [], escape: '');
        fputcsv($out, ['Best sellers', 'Units', 'Revenue (PHP)'], escape: '');
        foreach ($report['top_products'] as $p) {
            fputcsv($out, [$p['name'].' ('.$p['itemCode'].')', $p['units'], number_format((float) $p['revenue'], 2, '.', '')], escape: '');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $fileName = sprintf('shans-guitar-sales_%s_to_%s.csv', $period['from']->format('Y-m-d'), $period['to']->modify('-1 day')->format('Y-m-d'));

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    private function period(Request $request): array
    {
        return $this->salesReport->resolveRange(
            $request->query->getString('range', '30d'),
            $request->query->getString('from'),
            $request->query->getString('to'),
        );
    }
}
