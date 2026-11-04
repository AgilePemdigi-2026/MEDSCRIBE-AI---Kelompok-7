Feature: Poliklinik Dokter - Rekam Medis dan AI Medical Scribe
  In order to conduct outpatient consultations and record patient clinical findings
  As a clinic doctor
  I want to review patient vital signs, record the clinical interview, and replay the consultation audio

  Background:
    Given a patient is in today's queue at my hospital
    And the nurse has recorded the patient's vital signs, so the queue status is "Siap Periksa"

  Scenario: Open Patient Medical Record
    Given I am logged in as a doctor
    And I am on the "/dokter/dashboard" page
    When I press "Buka Rekam Medis" on the patient
    Then I should see the medical record page of the patient
    And I should see "TTV & Catatan Perawat"
    And I should see the button "Mulai Wawancara"
    And the queue status and the visit status should change to "Sedang Diperiksa"

  Scenario: Review Vital Signs Recorded by the Nurse
    Given the nurse recorded "Tekanan Darah" "130/85", "Suhu Tubuh" "37.2", "Denyut Nadi" "84", and the note "Keluhan batuk berdahak 4 hari"
    And I am logged in as a doctor
    When I open the patient's medical record
    Then I should see the blood pressure "130/85"
    And I should see the temperature "37.2"
    And I should see the pulse "84"
    And I should see the nurse's note "Keluhan batuk berdahak 4 hari"

  Scenario: Start Clinical Interview Session
    Given I am logged in as a doctor
    And I am on the patient's medical record page
    When I press "Mulai Wawancara"
    Then I should be returned to the "Rekam Wawancara Klinis" section of the medical record page
    And I should see the message "Wawancara dimulai. Rekam audio untuk membuat transkrip."
    And the interview session status should be "Wawancara Berlangsung"

  Scenario: Upload Interview Audio Recording Success
    Given I am logged in as a doctor
    And I have started the interview session on the patient's medical record page
    When I send the recorded interview audio "konsultasi.webm"
    Then I should receive a "202 Accepted" response
    And I should see the message "Rekaman disimpan dan diantrikan ke service transkripsi lokal."
    And the recording should be stored with status "queued"
    And the recording should be queued for local transcription

  Scenario: Upload Audio Rejected Before the Interview Is Started
    Given I am logged in as a doctor
    And I am on the patient's medical record page without starting the interview
    When I send an interview audio recording
    Then I should receive an error response with status "422 Unprocessable Entity"
    And I should see the message "Mulai sesi wawancara sebelum merekam."
    And no recording should be stored or queued

  Scenario: Upload Audio Rejected for Invalid File
    Given I am logged in as a doctor
    And I have started the interview session on the patient's medical record page
    When I send data that is not an audio file
    Then I should receive an error response with status "422 Unprocessable Entity"
    And I should see a validation error on the "audio" field

  Scenario: Stream Consultation Audio Recording
    Given I am logged in as a doctor
    And the patient's visit has a saved interview recording
    When I play the "Rekaman audio" on the medical record page
    Then the audio file should be streamed with status "200 OK" and content type "audio/wav"
