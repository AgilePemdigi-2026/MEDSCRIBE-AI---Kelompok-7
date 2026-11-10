Feature: Lupa dan Reset Password via OTP
  In order to regain access to my medical account if I forget my password
  As a healthcare professional
  I want to request a password reset OTP and set a new secure password

  Scenario: Request Password Reset Success
    Given I have a registered doctor account
    And I am on the "/forgot-password" page
    When I fill in "Email" with my registered email
    And I press "Kirim Kode Verifikasi"
    Then a password reset OTP should be created for my account
    And I should be redirected to the "/forgot-password/verify-otp" page for my email
    And I should see the message "Jika alamat email terdaftar di sistem, kode verifikasi 6 digit telah dikirimkan."

  Scenario: Request Password Reset With Unregistered Email
    Given I am on the "/forgot-password" page
    When I fill in "Email" with an email that is not registered
    And I press "Kirim Kode Verifikasi"
    Then I should be redirected to the "/forgot-password/verify-otp" page for that email
    And I should see the same message "Jika alamat email terdaftar di sistem, kode verifikasi 6 digit telah dikirimkan."
    And no OTP should be created for that email

  Scenario: Verify Password Reset OTP Success
    Given I have an active password reset OTP code "789123"
    And I am on the "/forgot-password/verify-otp" page
    When I fill in "Kode Verifikasi (6 Digit)" with "789123"
    And I press "Verifikasi Kode & Lanjutkan"
    Then the OTP code should be marked as used
    And I should be redirected to the "/reset-password" page with a reset token
    And I should see the message "Verifikasi berhasil. Silakan tentukan kata sandi baru Anda."

  Scenario: Failed Verify Password Reset OTP (wrong code)
    Given I have an active password reset OTP code "789123"
    And I am on the "/forgot-password/verify-otp" page
    When I fill in "Kode Verifikasi (6 Digit)" with "000000"
    And I press "Verifikasi Kode & Lanjutkan"
    Then I should see the error "Kode verifikasi tidak valid. Sisa percobaan: 4 kali."

  Scenario: Reset Password Success
    Given I have verified my password reset OTP and received a reset token
    And I am on the "/reset-password" page
    When I fill in "Password Baru" with "PasswordBaru#2026"
    And I fill in "Konfirmasi Password Baru" with "PasswordBaru#2026"
    And I press "Simpan Password Baru"
    Then my password should be updated
    And I should be redirected to "/login"
    And I should see the message "Password berhasil diperbarui. Silakan login menggunakan password baru Anda."
    And I should be able to log in with the new password
