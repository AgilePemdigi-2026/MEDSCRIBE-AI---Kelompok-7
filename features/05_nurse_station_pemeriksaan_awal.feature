Feature: Nurse Station - Manajemen Antrean dan Input Pemeriksaan Awal TTV
  In order to prepare and record objective patient vital signs for outpatient consultation
  As a clinic nurse
  I want to manage the queue, register new patients, and record vital signs (TTV)

  Scenario: Register New Patient with Identity Data Only
    Given I am logged in as a nurse
    And I am on the "/perawat/dashboard" page
    When I press "Input Pasien"
    Then I should see the modal "Input Pasien Baru"
    When I fill in "Nama lengkap" with "Andi Prasetyo"
    And I fill in "Tanggal lahir" with "1990-05-15"
    Then the "Umur" field should be filled automatically with the age from the birth date
    When I fill in "NIK" with "3578012345670001", "No. BPJS" with "0001234567890", and "No. HP" with "081234567890"
    And I press "Simpan dan isi TTV"
    Then the patient should be saved with a generated medical record number
    And a queue entry with status "Menunggu TTV" should be created for the patient
    And I should be redirected to the vital signs form of that patient on "/perawat/dashboard"
    And I should see the message "Pasien berhasil didaftarkan dengan No. RM <nomor rekam medis>. Lanjutkan dengan mengisi TTV."

  Scenario: Register Emergency Patient with Name Only
    Given I am logged in as a nurse
    When I register a new patient filling in only "Nama lengkap" with "Tn. X (tanpa identitas)"
    Then the patient should be saved without age and gender
    And a queue entry with status "Menunggu TTV" should be created for the patient
    And no initial examination or SOAP draft should exist yet

  Scenario: Search Patient in Queue
    Given I am logged in as a nurse
    And the queue contains "Budi Santoso BDD" and "Siti Aminah BDD" with status "Menunggu TTV"
    And I am on the "/perawat/dashboard" page
    When I fill in "Cari pasien" with "Budi Santoso BDD" and submit the search
    Then I should see "Budi Santoso BDD"
    And I should not see "Siti Aminah BDD"

  Scenario: Record Vital Signs (TTV) for Waiting Patient
    Given I am logged in as a nurse
    And a patient is in the queue with status "Menunggu TTV"
    And I have selected that patient on the "/perawat/dashboard" page
    When I fill in "Tekanan Darah" with "120/80"
    And I fill in "Denyut Nadi" with "80"
    And I fill in "Suhu Tubuh" with "36.8"
    And I fill in "Laju Pernapasan" with "18"
    And I fill in "SpO2" with "98"
    And I fill in "Berat Badan" with "65"
    And I fill in "Tinggi Badan" with "170"
    And I fill in "Catatan perawat" with "Pusing sejak kemarin malam"
    And I press "Simpan data pemeriksaan"
    Then the initial examination should be saved with the values I entered
    And I should be returned to the selected patient on "/perawat/dashboard"
    And I should see the message "Data berhasil disimpan!"
    And the queue status should be updated to "Siap Periksa"
    And the Objective section of the doctor's SOAP draft should contain "TD 120/80", "Nadi 80", and "Suhu 36,8°C"

  Scenario: Record Vital Signs for an Emergency Patient
    Given I am logged in as a nurse
    And a patient is in the queue with status "Menunggu TTV"
    When I fill in only "Tekanan Darah", "Denyut Nadi", "Suhu Tubuh", "Laju Pernapasan", and "SpO2"
    And I press "Simpan data pemeriksaan"
    Then the initial examination should be saved without weight, height, and nurse notes
    And the queue status should be updated to "Siap Periksa"

  Scenario: Cancel Patient Registration
    Given I am logged in as a nurse
    And a patient is in the queue with status "Menunggu TTV"
    And I am on the "/perawat/dashboard?tab=pasien" page
    When I press "Hapus" on that patient
    Then I should see the modal "Hapus Registrasi Pasien?"
    When I press "Ya, Hapus Registrasi"
    Then the patient registration should be cancelled (soft deleted)
    And the cancellation should record my nurse ID and the reason "Pasien tidak hadir / pembatalan registrasi oleh perawat"
    And the queue status should be updated to "Dibatalkan"
    And I should be redirected to "/perawat/dashboard?tab=pasien"
    And I should see the message "Registrasi pasien <nama pasien> (<nomor rekam medis>) berhasil dibatalkan."
