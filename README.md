# qrArt

## Analytics Setup

This project can display Google Analytics metrics using the Google Analytics Data API. To enable analytics:

1. Create a Google Cloud project and enable the Analytics Data API.
2. Generate a service account and download its JSON credentials file.
3. Place the credentials file somewhere on the server and note its path.
4. Set the following environment variables for the backend application:
   - `GA_CREDENTIALS_PATH` – path to the credentials JSON file
   - `GA_PROPERTY_ID` – ID of the Google Analytics 4 property to query
5. Alternatively you can set default values in `app/Config/Services.php` using `$gaCredentialsPath` and `$gaPropertyId`.
6. After updating the environment, run `composer install` inside `backend/qrartApp` to install the `google/apiclient` dependency.
7. Visit `/analytics/overview` to view a basic analytics report.
