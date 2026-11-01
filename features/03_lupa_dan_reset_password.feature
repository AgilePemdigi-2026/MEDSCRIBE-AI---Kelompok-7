Feature: Lupa dan Reset Password via OTP
  In order to regain access to my medical account if I forget my password
  As a healthcare professional
  I want to request a password reset OTP and set a new secure password

  Scenario: Request Password Reset Success
    Given I am on the "/forgot-password" page
    And I fill in "Email" with "dewi@medscribe.id"
    When I press "Kirim Kode Verifikasi"
    Then I should be redirected to "/forgot-password/verify-otp"
    And I should see "Verifikasi Kode OTP Reset Password"

  Scenario: Verify Password Reset OTP Success
    Given I am on the "/forgot-password/verify-otp" page
    And I fill in "Kode OTP" with "789123"
    When I press "Verifikasi OTP"
    Then I should be redirected to "/reset-password"
    And I should see "Atur Ulang Kata Sandi"

  Scenario: Failed Verify Password Reset OTP (wrong code)
    Given I am on the "/forgot-password/verify-otp" page
    And I fill in "Kode OTP" with "111111"
    When I press "Verifikasi OTP"
    Then I should see the message "Kode verifikasi tidak valid atau telah kedaluwarsa."
    And I should remain on the "/forgot-password/verify-otp" page

  Scenario: Reset Password Success
    Given I am on the "/reset-password" page
    And I fill in "Kata Sandi Baru" with "PasswordBaru#2026"
    And I fill in "Konfirmasi Kata Sandi" with "PasswordBaru#2026"
    When I press "Simpan Password Baru"
    Then I should be redirected to "/login"
    And I should see the message "Password berhasil diperbarui. Silakan login menggunakan password baru Anda."

  Scenario: Failed Reset Password (passwords do not match)
    Given I am on the "/reset-password" page
    And I fill in "Kata Sandi Baru" with "PasswordBaru#2026"
    And I fill in "Konfirmasi Kata Sandi" with "PasswordBeda#999"
    When I press "Simpan Password Baru"
    Then I should see the message "Konfirmasi password tidak cocok."
    And I should remain on the "/reset-password" page
