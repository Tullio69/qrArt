<?php
namespace App\Controllers;

class AnalyticsController extends BaseController
{
    public function overview(): string
    {
        $service = service('googleAnalytics');

        $end   = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-7 days'));

        try {
            $report = $service->getOverview($start, $end);
            return view('analytics/overview', ['report' => $report]);
        } catch (\Throwable $e) {
            return view('analytics/overview', ['error' => $e->getMessage()]);
        }
    }
}
