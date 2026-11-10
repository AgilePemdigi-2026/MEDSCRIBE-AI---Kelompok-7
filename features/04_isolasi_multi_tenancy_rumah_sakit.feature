Feature: Isolasi Data Multi-Tenancy Berbasis Rumah Sakit
  In order to protect patient medical privacy and adhere to healthcare data regulations
  As a healthcare professional
  I want to only access clinical data belonging to my assigned hospital

  Background:
    Given "RS Universitas Airlangga" (RS A) and "RSUD Dr. Soetomo" (RS B) are active hospitals
    And each hospital has a doctor, a nurse, and a patient in today's queue with status "Siap Periksa"

  Scenario: Nurse Only Sees Patients from Assigned Hospital
    Given I am logged in as the nurse of RS A
    When I go to the "/perawat/dashboard" page
    Then I should see the patient registered at RS A
    And I should not see the patient registered at RS B

  Scenario: Doctor Blocked from Accessing Other Hospital Records
    Given I am logged in as the doctor of RS A
    When I go to the medical record URL "/dokter/rekam-medis/{id_antrean}" of the RS B patient
    Then I should see an error page with status "403 Forbidden"
    And I should see "Akses ditolak: Anda tidak memiliki wewenang untuk mengakses rekam medis dari rumah sakit lain."

  Scenario: Doctor Blocked from Uploading Interview Audio for Other Hospital Patient
    Given the RS B patient's visit is being handled by the doctor of RS B
    And I am logged in as the doctor of RS A
    When I upload interview audio to that RS B visit
    Then I should receive an error response with status "403 Forbidden"
    And the response message should be "Akses ditolak: Anda tidak memiliki wewenang untuk mengakses rekam medis dari rumah sakit lain."
    And no audio recording should be stored for that visit

  Scenario: Doctor Blocked from Streaming Audio of Other Hospital Patient
    Given I am logged in as the doctor of RS A
    When I request the consultation audio stream of the RS B patient
    Then I should receive an error response with status "403 Forbidden"

  Scenario: Nurse Blocked from Cancelling Other Hospital Patient
    Given I am logged in as the nurse of RS A
    When I attempt to cancel the registration of the RS B patient
    Then I should receive an error response with status "403 Forbidden"
    And I should see "Akses ditolak: Anda tidak berwenang membatalkan registrasi pasien rumah sakit lain."
    And the RS B patient data should remain intact

  Scenario: New Patient Automatically Bound to the Nurse's Hospital
    Given I am logged in as the nurse of RS A
    When I register a new patient "Bambang Sudarsono" with complete initial vital signs
    Then the patient should be saved without validation errors
    And the patient should belong to RS A
