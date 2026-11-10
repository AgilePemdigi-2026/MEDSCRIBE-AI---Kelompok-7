Feature: Nurse Station - Manajemen Antrean dan Input Pemeriksaan Awal TTV
  In order to prepare and record objective patient vital signs for outpatient consultation
  As a clinic nurse
  I want to manage the queue, register new patients, and record vital signs (TTV)

  Scenario: Register New Patient and Initial Vital Signs Success
    Given I am logged in as a nurse
    And I am on the "/perawat/dashboard" page
    When I press "Input Pasien"
    Then I should see the modal "Input Pasien Baru"
    When I fill in the patient's name with "Andi Prasetyo", age "35", and gender "Laki-laki"
    And I fill in "TD Sistolik" with "120" and "TD Diastolik" with "80"
    And I fill in "Denyut Nadi" with "80"
    And I fill in "Suhu Tubuh" with "36.6"
    And I fill in "Laju Pernapasan" with "18"
    And I fill in "SpO2" with "99"
    And I press "Simpan Pasien"
    Then the patient should be saved with a generated medical record number
    And a queue entry with status "Siap Periksa" should be created for the patient
    And I should be redirected to "/perawat/dashboard?tab=pasien"
    And I should see the message "Pasien berhasil didaftarkan dengan ID: <nomor rekam medis>"

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
    And I fill in "Berat Badan" with "65"
    And I fill in "Tinggi Badan" with "170"
    And I fill in "Catatan Awal Perawat" with "Pusing sejak kemarin malam"
    And I press "Simpan data pemeriksaan"
    Then the initial examination should be saved with the values I entered
    And I should be returned to the selected patient on "/perawat/dashboard"
    And I should see the message "Data berhasil disimpan!"
    And the queue status should be updated to "Siap Periksa"
    And the Objective section of the doctor's SOAP draft should contain "TD 120/80", "Nadi 80", and "Suhu 36,8°C"

  Scenario: High Fever Warning Indicator
    Given I am logged in as a nurse
    And a patient is in the queue with status "Menunggu TTV"
    When I save the patient's vital signs with "Suhu Tubuh" "38.8"
    And I open that patient on the "/perawat/dashboard" page
    Then I should see the temperature "38.8"
    And I should see the warning "Peringatan klinis: Suhu di atas rentang normal (>37.5°C)"

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
