Feature: Registrasi Akun Tenaga Medis dan Verifikasi OTP Email
  In order to join the hospital clinical workspace with a verified identity
  As a new healthcare professional
  I want to register an account and verify the OTP code sent to my email

  Scenario: Register Success
    Given I am on the "/register" page
    And I fill in "Nama Lengkap" with "dr. Siti Rahma"
    And I fill in "Email" with "siti.rahma@medscribe.id"
    And I select "Dokter" from the "Role" dropdown
    And I select "RS Universitas Airlangga" from the "Rumah Sakit" dropdown
    And I fill in "Password" with "Rahasia@2026"
    And I fill in "Konfirmasi Password" with "Rahasia@2026"
    When I press "Daftar Sekarang"
    Then I should be redirected to "/verify-email-otp"
    And I should see "Verifikasi Email Anda"
    And I should see the message "Kode OTP telah dikirimkan ke email Anda"

  Scenario: Failed Register (missing fields)
    Given I am on the "/register" page
    And I fill in "Nama Lengkap" with ""
    And I fill in "Email" with ""
    And I fill in "Password" with ""
    And I fill in "Konfirmasi Password" with ""
    When I press "Daftar Sekarang"
    Then I should see the message "Nama lengkap wajib diisi."
    And I should remain on the "/register" page

  Scenario: Failed Register (passwords do not match)
    Given I am on the "/register" page
    And I fill in "Nama Lengkap" with "dr. Budi Setiawan"
    And I fill in "Email" with "budi.setiawan@medscribe.id"
    And I select "Dokter" from the "Role" dropdown
    And I select "RS Universitas Airlangga" from the "Rumah Sakit" dropdown
    And I fill in "Password" with "Password@123"
    And I fill in "Konfirmasi Password" with "PasswordBeda@456"
    When I press "Daftar Sekarang"
    Then I should see the message "Konfirmasi password tidak cocok."
    And I should remain on the "/register" page

  Scenario: Failed Register (email already registered)
    Given I am on the "/register" page
    And I fill in "Nama Lengkap" with "dr. Rina Anggraini"
    And I fill in "Email" with "rina@medscribe.id"
    And I select "Dokter" from the "Role" dropdown
    And I select "RS Universitas Airlangga" from the "Rumah Sakit" dropdown
    And I fill in "Password" with "password123"
    And I fill in "Konfirmasi Password" with "password123"
    When I press "Daftar Sekarang"
    Then I should see the message "Email ini sudah terdaftar."
    And I should remain on the "/register" page

  Scenario: Verify Email OTP Success
    Given I am on the "/verify-email-otp" page
    And I fill in "Kode OTP" with "654321"
    When I press "Verifikasi Akun"
    Then I should be redirected to "/login"
    And I should see the message "Email Anda berhasil diverifikasi. Silakan masuk."

  Scenario: Failed Verify Email OTP (wrong code)
    Given I am on the "/verify-email-otp" page
    And I fill in "Kode OTP" with "000000"
    When I press "Verifikasi Akun"
    Then I should see the message "Kode verifikasi tidak valid atau telah kedaluwarsa."
    And I should remain on the "/verify-email-otp" page

  Scenario: Resend Email OTP Success
    Given I am on the "/verify-email-otp" page
    When I press "Kirim Ulang Kode"
    Then I should see the message "Kode verifikasi baru telah dikirimkan ke email Anda."
    And I should remain on the "/verify-email-otp" page
