<?php
namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

class AnalyticsController extends BaseController
{
    use ResponseTrait;

    protected $helpers = ['form', 'url'];

    public function __construct()
    {
        helper(['form', 'url']);
    }

    public function index()
    {
        return redirect()->to('/analytics/overview');
    }

    public function overview()
    {
        $service = service('googleAnalytics');
        
        // Default to last 7 days
        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-7 days'));
        
        // Get date range from request if provided
        $dateRange = $this->request->getGet('range');
        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $start = $end;
                    break;
                case 'yesterday':
                    $start = $end = date('Y-m-d', strtotime('-1 day'));
                    break;
                case '7days':
                    $start = date('Y-m-d', strtotime('-7 days'));
                    break;
                case '30days':
                    $start = date('Y-m-d', strtotime('-30 days'));
                    break;
                case '90days':
                    $start = date('Y-m-d', strtotime('-90 days'));
                    break;
                case 'custom':
                    $start = $this->request->getGet('start_date') ?: $start;
                    $end = $this->request->getGet('end_date') ?: $end;
                    break;
            }
        }

        try {
            $data = [
                'startDate' => $start,
                'endDate' => $end,
                'selectedRange' => $dateRange ?: '7days',
                'pageTitle' => 'Analytics Dashboard'
            ];

            $analyticsData = $service->getOverview($start, $end);
            $data = array_merge($data, $analyticsData);

            if ($this->request->isAJAX()) {
                return $this->response->setJSON($data);
            }

            return view('analytics/overview', $data);
        } catch (\Throwable $e) {
            if ($this->request->isAJAX()) {
                return $this->failServerError($e->getMessage());
            }
            
            return view('analytics/overview', [
                'error' => $e->getMessage(),
                'pageTitle' => 'Analytics Dashboard'
            ]);
        }
    }
    
    public function trackScan($qrCodeId)
    {
        $service = service('googleAnalytics');
        
        $userData = [
            'user_agent' => $this->request->getUserAgent(),
            'ip_address' => $this->request->getIPAddress(),
            'referrer' => $this->request->getServer('HTTP_REFERER')
        ];
        
        $success = $service->trackScan($qrCodeId, $userData);
        
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => $success]);
        }
        
        return redirect()->back()->with('message', $success ? 'Scan tracked successfully' : 'Failed to track scan');
    }
}
