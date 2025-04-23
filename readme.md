## Serwis z wydarzeniami miasta backend
Backend projektu napisany w PHP (Symfony), służący do zapisu i zarządzania wydarzeniami miasta Płock. Dane są pobierane zarówno z zewnętrznych API, jak i przez web scraping. Backend udostępnia REST API, z którego korzysta frontend w React.

## Technologie i narzędzia
- **Symfony PHP**
- **Doctrine ORM** – obsługa bazy danych (MSSQL)
- **HTTP Client** – pobieranie danych z zewnętrznych API
- **Web scraping**
- **REST API** – komunikacja z frontendem
- **JWT** – logowanie użytkownika
- **Google OAuth2** – logowanie za pomocą Google
- **Google Calendar API** – zapis wydarzeń do kalendarza Google

## Funkcjonalności
- Pobieranie wydarzeń z zewnętrznych źródeł (API oraz strony internetowe)
- Zapisywanie wydarzeń w bazie danych jako encje
- Uwierzytelnianie:
  - logowanie standardowe (email + hasło)
  - logowanie przez Google OAuth2
- Dodawanie wydarzenia do Kalendarza Google
- Udostępnianie danych do frontendu przez REST API
- Obsługa relacji użytkownik-wydarzenia
- Administracja serwisem

## Możliwości użytkownika
- Przeglądanie wydarzeń przez aplikację frontendową
- Rejestracja i logowanie
- Tworzenie i zarządzanie własnymi wydarzeniami
- Dodawanie wydarzeń do swojego kalendarza Google
- Otrzymywanie aktualnych informacji o wydarzeniach z miasta

## Komunikacja z frontendem
Aplikacja udostępnia REST API (JSON), z którym łączy się frontend napisany w React. Obsługuje zapytania GET/POST/DELETE do operacji na wydarzeniach i użytkownikach.
