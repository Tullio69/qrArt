<?php
namespace App\Libraries;

use Google\Client;
use Google\Service\AnalyticsData;
use Google\Service\AnalyticsData\RunReportRequest;

class GoogleAnalyticsService
{
    protected AnalyticsData $analytics;
    protected string $propertyId;

    public function __construct()
    {
        $credentialsPath = getenv('GA_CREDENTIALS_PATH') ?: config('Services')->gaCredentialsPath ?? '';
        $this->propertyId  = getenv('GA_PROPERTY_ID') ?: config('Services')->gaPropertyId ?? '';

        $client = new Client();
        if ($credentialsPath) {
            $client->setAuthConfig($credentialsPath);
        }
        $client->addScope(AnalyticsData::ANALYTICS_READONLY);
        $this->analytics = new AnalyticsData($client);
    }

    public function getOverview(string $startDate, string $endDate)
    {
        $request = new RunReportRequest([
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate]
            ],
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'screenPageViews'],
            ],
        ]);

        return $this->analytics->properties->runReport("properties/{$this->propertyId}", $request);
    }
}
