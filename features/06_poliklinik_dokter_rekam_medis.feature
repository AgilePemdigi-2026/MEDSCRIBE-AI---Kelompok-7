Feature: Poliklinik Dokter - Rekam Medis dan AI Medical Scribe
  In order to conduct outpatient consultations and record patient clinical findings
  As a clinic doctor
  I want to review patient vital signs, record consultation audio, and review the medical record

  Scenario: Open Patient Medical Record
    Given I am logged in as a doctor
    And I am on the "/dokter/dashboard" page
    When I press "Buka Rekam Medis" on a patient with status "Siap Periksa"
    Then I should be redirected to the medical record page
    And I should see "TTV & Catatan Perawat"
    And I should see "Rekam Wawancara Klinis"
    And I should see the consultation status "Sedang Diperiksa"

  Scenario: Review Pre-filled Vital Signs from Nurse
    Given I am logged in as a doctor
    When I am on the medical record page of a patient who has completed TTV
    Then I should see the vital signs "125/85"
    And I should see the temperature "37.2"
    And I should see the nurse's clinical note

  Scenario: Upload Consultation Audio Recording Success
    Given I am logged in as a doctor
    And I am on the medical record page
    When I attach the audio file "konsultasi.wav" to the recording uploader
    And I press "Unggah Rekaman"
    Then I should see the message "Rekaman wawancara klinis berhasil disimpan di server."
    And I should see the recording status "Tersimpan"

  Scenario: Failed Upload Audio (no file attached)
    Given I am logged in as a doctor
    And I am on the medical record page
    When I press "Unggah Rekaman" without selecting a file
    Then I should see the error message "Berkas audio wajib diunggah."

  Scenario: Stream Consultation Audio Recording
    Given I am logged in as a doctor
    And I am on the medical record page of a patient with a saved recording
    When I press the audio player play button
    Then I should hear the consultation audio playback
