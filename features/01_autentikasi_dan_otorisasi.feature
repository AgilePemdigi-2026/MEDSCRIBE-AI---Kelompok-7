Feature: Autentikasi Tenaga Medis (Login & Logout)
  In order to access electronic medical records securely according to my clinical role
  As a healthcare professional (Doctor or Nurse)
  I want to log in and log out of the MedScribe AI system

  Scenario: Doctor Login Success
    Given I am on the "/login" page
    And I fill in "Email" with "rina@medscribe.id"
    And I fill in "Password" with "password123"
    When I press "Masuk"
    Then I should be redirected to "/dokter/dashboard"
    And I should see "dr. Rina Anggraini, Sp.PD"
    And I should see "Dokter Poliklinik"

  Scenario: Nurse Login Success
    Given I am on the "/login" page
    And I fill in "Email" with "dewi@medscribe.id"
    And I fill in "Password" with "password123"
    When I press "Masuk"
    Then I should be redirected to "/perawat/dashboard"
    And I should see "Dewi Pratiwi"
    And I should see "Perawat"

  Scenario: Failed Login (missing fields)
    Given I am on the "/login" page
    And I fill in "Email" with ""
    And I fill in "Password" with ""
    When I press "Masuk"
    Then I should see the message "Email wajib diisi."
    And I should remain on the "/login" page

  Scenario: Failed Login (email not registered)
    Given I am on the "/login" page
    And I fill in "Email" with "unknown.doctor@medscribe.id"
    And I fill in "Password" with "password123"
    When I press "Masuk"
    Then I should see the message "Kredensial yang diberikan tidak cocok dengan data kami."
    And I should remain on the "/login" page

  Scenario: Failed Login (wrong password)
    Given I am on the "/login" page
    And I fill in "Email" with "rina@medscribe.id"
    And I fill in "Password" with "wrongpassword999"
    When I press "Masuk"
    Then I should see the message "Kredensial yang diberikan tidak cocok dengan data kami."
    And I should remain on the "/login" page

  Scenario: Failed Login (account pending approval)
    Given I am on the "/login" page
    And I fill in "Email" with "pending.nurse@medscribe.id"
    And I fill in "Password" with "password123"
    When I press "Masuk"
    Then I should see the message "Akun Anda sedang menunggu persetujuan administrator rumah sakit."
    And I should remain on the "/login" page

  Scenario: Authenticated User Redirected From Login Page
    Given I am logged in as a doctor
    When I go to the "/login" page
    Then I should be redirected to "/dashboard"

  Scenario: Logout Success
    Given I am logged in as a doctor
    And I am on the "/dokter/dashboard" page
    When I press "Keluar dari Sistem" button
    Then I should see the modal "modalLogout"
    When I press "Keluar" button in the modal
    Then I should be redirected to "/login"
    And I should see "Masuk ke Akun MedScribe AI"
