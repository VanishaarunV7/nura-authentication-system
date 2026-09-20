# Application Flow

## Nura Authentication System

This document describes the complete authentication and profile management flow of the Nura Authentication System.

The application uses:

- HTML5
- CSS3
- Bootstrap
- JavaScript
- jQuery
- AJAX
- PHP
- MySQL
- MongoDB
- Redis

## Complete Application Flow

```mermaid
flowchart TD
    A[User] --> B[Register Page]

    B -->|jQuery AJAX POST| C[register.php]

    C --> D[Validate Input]
    D -->|Valid| E[password_hash]
    E --> F[(MySQL users)]
    F --> G[Registration Success]
    G --> H[Login Page]

    D -->|Invalid| B

    H -->|jQuery AJAX POST| I[login.php]

    I --> J[Validate Login Input]
    J --> K[(MySQL users)]
    K --> L[Retrieve User]
    L --> M[password_verify]

    M -->|Valid Credentials| N[Generate Secure Auth Token]
    N --> O[(Redis Session)]
    O -->|Store User ID + TTL| P[Set HttpOnly auth_token Cookie]
    P --> Q[Profile Page]

    M -->|Invalid Credentials| R[Return Login Error]
    R --> H

    Q -->|jQuery AJAX GET| S[profile.php]

    S --> T[Read auth_token Cookie]
    T --> U[(Redis Session)]
    U -->|Valid Session| V[Authenticate User]

    U -->|Invalid / Expired Session| W[Return HTTP 401]
    W --> H

    V --> X[(MySQL users)]
    X --> Y[Read Username and Email]

    V --> Z[(MongoDB profiles)]
    Z --> AA[Read Additional Profile Data]

    Y --> AB[Return Profile JSON]
    AA --> AB
    AB --> Q

    Q -->|jQuery AJAX POST| S
    S --> AC[Validate Profile Data]
    AC -->|Valid| AD[(MongoDB profiles)]
    AD -->|Upsert Profile| AE[Profile Updated]
    AE --> Q

    AC -->|Invalid| AF[Return Validation Error]
    AF --> Q

    Q -->|AJAX Logout| S
    S --> AG[(Redis Session)]
    AG -->|Delete Session| AH[Clear auth_token Cookie]
    AH --> AI[Logout Success]
    AI --> H