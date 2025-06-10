# qrArt

This project uses [CodeIgniter 4](https://codeigniter.com/) for the backend.

## Configuration

Create a `.env` file in `backend/qrartApp` with your database credentials.  The following variables are read by the application:

```
database.default.hostname
database.default.username
database.default.password
database.default.database
```

Example:

```
database.default.hostname = localhost
database.default.username = developer.qrart
database.default.password = pOngE0oYSiVAtRZ
database.default.database = qrart
```

These values will override the defaults defined in `app/Config/Database.php` when the application runs.
