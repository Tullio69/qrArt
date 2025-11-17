<?php
namespace App\Libraries;

use Google\Client;
use Google\Service\AnalyticsData;
use Google\Service\AnalyticsData\RunReportRequest;
use Google\Service\AnalyticsData\DateRange;
use Google\Service\AnalyticsData\Dimension;
use Google\Service\AnalyticsData\Metric;

class GoogleAnalyticsService
{
    protected AnalyticsData $analytics;
    protected string $propertyId;
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $credentialsPath = getenv('GA_CREDENTIALS_PATH') ?: config('Services')->gaCredentialsPath ?? '';
        $this->propertyId = getenv('GA_PROPERTY_ID') ?: config('Services')->gaPropertyId ?? '';

        if (empty($this->propertyId)) {
            throw new \RuntimeException('Google Analytics property ID is not configured');
        }

        if ($credentialsPath && file_exists($credentialsPath)) {
            $client = new Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope(AnalyticsData::ANALYTICS_READONLY);
            $this->analytics = new AnalyticsData($client);
        }
    }

    public function getOverview(string $startDate, string $endDate): array
    {
        $gaData = [];
        
        if (isset($this->analytics)) {
            // Get user and session metrics
            $gaData = $this->getGAData($startDate, $endDate);
        }
        
        // Get QR code scan data from database
        $qrStats = $this->getQRCodeStats($startDate, $endDate);
        
        return array_merge($gaData, $qrStats);
    }
    
    protected function getGAData(string $startDate, string $endDate): array
    {
        $request = new RunReportRequest([
            'dateRanges' => [
                new DateRange([
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ])
            ],
            'metrics' => [
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'bounceRate'])
            ],
            'dimensions' => [
                new Dimension(['name' => 'date'])
            ],
            'orderBys' => [
                ['dimension' => ['dimensionName' => 'date', 'orderType' => 'NUMERIC']]
            ]
        ]);

        $response = $this->analytics->properties->runReport("properties/{$this->propertyId}", $request);
        
        // Process and format the response
        $result = [
            'totalSessions' => 0,
            'totalUsers' => 0,
            'totalPageViews' => 0,
            'avgSessionDuration' => 0,
            'avgBounceRate' => 0,
            'dailyStats' => []
        ];
        
        foreach ($response->getRows() as $row) {
            $date = $row->getDimensionValues()[0]->getValue();
            $metrics = $row->getMetricValues();
            
            $result['dailyStats'][] = [
                'date' => $date,
                'sessions' => (int)$metrics[0]->getValue(),
                'users' => (int)$metrics[1]->getValue(),
                'pageViews' => (int)$metrics[2]->getValue(),
                'avgDuration' => (float)$metrics[3]->getValue(),
                'bounceRate' => (float)$metrics[4]->getValue()
            ];
            
            $result['totalSessions'] += (int)$metrics[0]->getValue();
            $result['totalUsers'] += (int)$metrics[1]->getValue();
            $result['totalPageViews'] += (int)$metrics[2]->getValue();
            $result['avgSessionDuration'] += (float)$metrics[3]->getValue();
            $result['avgBounceRate'] += (float)$metrics[4]->getValue();
        }
        
        $dayCount = count($result['dailyStats']);
        if ($dayCount > 0) {
            $result['avgSessionDuration'] = $result['avgSessionDuration'] / $dayCount;
            $result['avgBounceRate'] = $result['avgBounceRate'] / $dayCount;
        }
        
        return $result;
    }
    
    protected function getQRCodeStats(string $startDate, string $endDate): array
    {
        $builder = $this->db->table('qr_scans')
            ->select('DATE(created_at) as scan_date, COUNT(*) as scan_count, qr_code_id')
            ->where('created_at >=', $startDate)
            ->where('created_at <=', $endDate . ' 23:59:59')
            ->groupBy('scan_date, qr_code_id')
            ->orderBy('scan_date', 'ASC');
            
        $scans = $builder->get()->getResultArray();
        
        // Get top performing QR codes
        $topQRCodes = $this->db->table('qr_scans')
            ->select('qr_code_id, qr_codes.name, COUNT(*) as total_scans')
            ->join('qr_codes', 'qr_codes.id = qr_scans.qr_code_id')
            ->where('qr_scans.created_at >=', $startDate)
            ->where('qr_scans.created_at <=', $endDate . ' 23:59:59')
            ->groupBy('qr_code_id, qr_codes.name')
            ->orderBy('total_scans', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
            
        return [
            'totalScans' => array_sum(array_column($scans, 'scan_count')),
            'scansByDate' => $scans,
            'topQRCodes' => $topQRCodes
        ];
    }
    
    public function trackScan(string $qrCodeId, array $userData = []): bool
    {
        try {
            $this->db->table('qr_scans')->insert([
                'qr_code_id' => $qrCodeId,
                'user_agent' => $userData['user_agent'] ?? null,
                'ip_address' => $userData['ip_address'] ?? null,
                'referrer' => $userData['referrer'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Failed to track QR scan: ' . $e->getMessage());
            return false;
        }
    }
}
