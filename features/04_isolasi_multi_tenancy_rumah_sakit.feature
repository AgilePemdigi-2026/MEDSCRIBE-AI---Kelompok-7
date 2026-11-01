Feature: Isolasi Data Multi-Tenancy Berbasis Rumah Sakit
  In order to protect patient medical privacy and adhere to healthcare data regulations
  As a healthcare professional
  I want to only access clinical data belonging to my assigned hospital

  Scenario: Nurse Only Sees Patients from Assigned Hospital
    Given I am logged in as a nurse assigned to "RS Universitas Airlangga"
    When I go to the "/perawat/dashboard" page
    Then I should see patients registered at "RS Universitas Airlangga"
    And I should not see patients registered at "RSUD Dr. Soetomo"

  Scenario: Doctor Blocked from Accessing Other Hospital Records
    Given I am logged in as a doctor assigned to "RS Universitas Airlangga"
    When I go to the medical record URL of a patient from "RSUD Dr. Soetomo"
    Then I should see an error page with status "403 Forbidden"
    And I should see "Akses ditolak: Hanya dokter poliklinik rumah sakit terkait yang berwenang membuka rekam medis."

  Scenario: Doctor Blocked from Uploading Audio for Other Hospital Patient
    Given I am logged in as a doctor assigned to "RS Universitas Airlangga"
    When I attempt to upload consultation audio to a patient from "RSUD Dr. Soetomo"
    Then I should receive an error response with status "403 Forbidden"

  Scenario: Doctor Blocked from Streaming Audio of Other Hospital Patient
    Given I am logged in as a doctor assigned to "RS Universitas Airlangga"
    When I attempt to stream audio of a patient from "RSUD Dr. Soetomo"
    Then I should receive an error response with status "403 Forbidden"

  Scenario: Nurse Blocked from Cancelling Other Hospital Patient
    Given I am logged in as a nurse assigned to "RS Universitas Airlangga"
    When I attempt to cancel a patient registered at "RSUD Dr. Soetomo"
    Then I should receive an error response with status "403 Forbidden"
