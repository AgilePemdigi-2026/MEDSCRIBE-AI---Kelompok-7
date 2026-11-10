Feature: Registrasi Akun Tenaga Medis dan Verifikasi OTP Email
  In order to join the hospital clinical workspace with a verified identity
  As a new healthcare professional
  I want to register an account and verify the OTP code sent to my email

  Scenario: Register Success
    Given "RS Universitas Airlangga" is an active hospital
    And I am on the "/register" page
    When I fill in "Nama Lengkap & Gelar" with "Siti Rahma"
    And I fill in "Email" with a new email address
    And I choose "Perawat" as "Peran Tenaga Medis"
    And I select "RS Universitas Airlangga" from "Rumah Sakit Tempat Bertugas"
    And I fill in "Kata Sandi" with "Rahasia@2026"
    And I fill in "Konfirmasi Kata Sandi" with "Rahasia@2026"
    And I press "Daftar & Terima Kode Verifikasi"
    Then my registration should be stored as pending until the email is verified
    And I should be redirected to the "/verify-email-otp" page for my email
    And I should see the message "Registrasi berhasil dimulai. Silakan periksa email Anda dan masukkan kode OTP untuk menyelesaikan pendaftaran."

  Scenario: Failed Register (email already registered)
    Given an active doctor account already uses the email I want to register
    And I am on the "/register" page
    When I fill in the registration form with that email
    And I press "Daftar & Terima Kode Verifikasi"
    Then I should see the error "Email ini sudah terdaftar. Silakan login menggunakan akun Anda atau gunakan fitur Lupa Password jika Anda tidak dapat mengakses akun."

  Scenario: Failed Register (passwords do not match)
    Given I am on the "/register" page
    When I fill in "Kata Sandi" with "Password@123"
    And I fill in "Konfirmasi Kata Sandi" with "PasswordBeda@123"
    And I fill in the other registration fields with valid data
    And I press "Daftar & Terima Kode Verifikasi"
    Then I should see the error "Konfirmasi kata sandi tidak cocok."

  Scenario: Verify Email OTP Success
    Given I have a pending registration with the active OTP code "654321"
    And I am on the "/verify-email-otp" page
    When I fill in "Kode Verifikasi (6 Digit)" with "654321"
    And I press "Verifikasi Email"
    Then my account should be created with a verified email
    And my pending registration should be removed
    And I should be redirected to "/login"
    And I should see the message "Verifikasi email berhasil. Akun Anda telah berhasil didaftarkan. Silakan login untuk melanjutkan."

  Scenario: Failed Verify Email OTP (wrong code)
    Given I have a pending registration with the active OTP code "654321"
    And I am on the "/verify-email-otp" page
    When I fill in "Kode Verifikasi (6 Digit)" with "111111"
    And I press "Verifikasi Email"
    Then I should see the error "Kode verifikasi tidak valid. Sisa percobaan: 4 kali."
    And my account should not be created

  Scenario: Failed Verify Email OTP (expired code)
    Given I have a pending registration whose OTP code "654321" has expired
    And I am on the "/verify-email-otp" page
    When I fill in "Kode Verifikasi (6 Digit)" with "654321"
    And I press "Verifikasi Email"
    Then I should see the error "Kode verifikasi telah kedaluwarsa. Silakan minta kode verifikasi baru."

  Scenario: Resend Email OTP Success
    Given I have a pending registration whose last OTP was sent more than 60 seconds ago
    And I am on the "/verify-email-otp" page
    When I press "Kirim Ulang Kode"
    Then I should see the message "Kode verifikasi baru telah dikirimkan ke email Anda."
    And the new OTP code should have a validity period in the future
