Feature: Autentikasi Tenaga Medis (Login & Logout)
  In order to access electronic medical records securely according to my clinical role
  As a healthcare professional (Doctor or Nurse)
  I want to log in and log out of the MedScribe AI system

  Scenario: Doctor Login Success
    Given an approved and verified doctor account exists
    And I am on the "/login" page
    When I fill in "Email" and "Kata Sandi" with the doctor's credentials
    And I press "Masuk ke Sistem"
    Then I should be authenticated as the doctor
    And I should be redirected to "/dokter/dashboard"
    And I should see the message "Selamat datang kembali, <nama dokter>!"

  Scenario: Nurse Login Success
    Given an approved and verified nurse account exists
    And I am on the "/login" page
    When I fill in "Email" and "Kata Sandi" with the nurse's credentials
    And I press "Masuk ke Sistem"
    Then I should be authenticated as the nurse
    And I should be redirected to "/perawat/dashboard"

  Scenario: Failed Login (wrong password)
    Given an approved and verified doctor account exists
    And I am on the "/login" page
    When I fill in "Email" with the doctor's email
    And I fill in "Kata Sandi" with a wrong password
    And I press "Masuk ke Sistem"
    Then I should remain a guest
    And I should see the error "Email atau kata sandi tidak valid."

  Scenario: Failed Login (email not registered)
    Given I am on the "/login" page
    When I fill in "Email" with an email that is not registered
    And I fill in "Kata Sandi" with "password123"
    And I press "Masuk ke Sistem"
    Then I should remain a guest
    And I should see the error "Email atau kata sandi tidak valid."

  Scenario: Failed Login (account pending approval)
    Given a verified nurse account exists with approval status "pending"
    And I am on the "/login" page
    When I fill in "Email" and "Kata Sandi" with the nurse's credentials
    And I press "Masuk ke Sistem"
    Then I should remain a guest
    And I should see the error "Akun Anda sedang dalam proses peninjauan persetujuan administratif rumah sakit."

  Scenario: Authenticated User Redirected From Login Page
    Given I am logged in as a doctor
    When I go to the "/login" page
    Then I should be redirected to "/dashboard"

  Scenario: Doctor Blocked from Nurse Station Pages
    Given I am logged in as a doctor
    When I request "/perawat/dashboard" as a JSON request
    Then I should receive an error response with status "403 Forbidden"
    When I open "/perawat/dashboard" in the browser
    Then I should be redirected to "/dokter/dashboard"
    And I should see the warning "Akses ditolak: Anda tidak memiliki akses ke halaman Nurse Station/Perawat."

  Scenario: Nurse Blocked from Doctor Pages
    Given I am logged in as a nurse
    When I request "/dokter/dashboard" as a JSON request
    Then I should receive an error response with status "403 Forbidden"
    When I open "/dokter/dashboard" in the browser
    Then I should be redirected to "/perawat/dashboard"
    And I should see the warning "Akses ditolak: Anda tidak memiliki akses ke halaman Dokter."

  Scenario: Logout Success
    Given I am logged in as a doctor
    And I am on the "/dokter/dashboard" page
    When I press "Keluar" in the sidebar
    Then I should see the modal "modalLogout"
    When I press "Keluar" in the modal
    Then I should no longer be authenticated
    And I should be redirected to "/login"
    And I should see the message "Anda telah berhasil keluar dari sistem MedScribe AI."
