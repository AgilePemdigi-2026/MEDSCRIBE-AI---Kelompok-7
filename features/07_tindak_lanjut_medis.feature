Feature: Kelola Tindak Lanjut Medis (E-Resep dan Rujukan Lab)
  In order to complete the patient's outpatient visit with the right follow-up
  As a clinic doctor
  I want to issue an e-prescription, a laboratory referral, or close the visit without further action

  Background:
    Given I am logged in as a doctor
    And I am examining a patient whose visit is assigned to me
    And the laboratory parameter "Darah Lengkap" with LOINC code "58410-2" is active

  Scenario: Follow-up Section Locked Before SOAP Is Verified
    Given the patient's SOAP draft has not been verified
    When I open the patient's medical record page
    Then I should see the section "Kelola Tindak Lanjut Medis"
    And I should see "Verifikasi draf SOAP untuk membuka tindak lanjut medis."
    And I should not see the follow-up form
    When I send a follow-up for this visit anyway
    Then I should receive an error response with status "422 Unprocessable Entity"
    And I should see the message "Draf SOAP harus diverifikasi sebelum tindak lanjut disimpan."
    And no follow-up should be saved

  Scenario: Follow-up Form Opens After SOAP Is Verified
    Given the patient's SOAP draft has been verified
    When I open the patient's medical record page
    Then I should see the follow-up form with the options "Buat E-Resep", "Buat Surat Rujukan Lab", and "Tanpa Tindak Lanjut"
    And I should be able to choose the laboratory parameter "Darah Lengkap"
    And I should see the button "Validasi & Simpan"

  Scenario: Save E-Prescription With Multiple Medicines
    Given the patient's SOAP draft has been verified
    When I check "Buat E-Resep"
    And I fill in "Nama Obat" "Paracetamol", "Dosis" "500 mg", "Bentuk Sediaan" "Tablet", "Jumlah Obat" "10", and "Aturan Minum" "3x Sehari"
    And I press "Tambah Obat"
    And I fill in "Nama Obat" "Amoksisilin", "Dosis" "500 mg", "Bentuk Sediaan" "Kapsul", "Jumlah Obat" "15", and "Aturan Minum" "3x Sehari"
    And I press "Validasi & Simpan"
    Then I should see the message "Resep obat berhasil disimpan."
    And both medicines should be saved in one follow-up for the patient
    And the visit status should be "Resep Tersimpan"
    And the medical record page should show the follow-up type "Resep Obat" with "Paracetamol" and "Amoksisilin"

  Scenario: Save Laboratory Referral
    Given the patient's SOAP draft has been verified
    When I check "Buat Surat Rujukan Lab"
    And I select "Darah Lengkap" from "Parameter Pemeriksaan Laboratorium"
    And I fill in "Catatan Rujukan" with "Evaluasi infeksi."
    And I press "Validasi & Simpan"
    Then I should see the message "Rujukan lab berhasil disimpan."
    And the referral should record the examination "Darah Lengkap" with LOINC code "58410-2"
    And the visit status should be "Rujukan Lab Tersimpan"
    And the medical record page should show "Rujukan Laboratorium" with "58410-2"

  Scenario: Save E-Prescription and Laboratory Referral Together
    Given the patient's SOAP draft has been verified
    When I check "Buat E-Resep" and "Buat Surat Rujukan Lab"
    And I fill in one medicine and select "Darah Lengkap"
    And I press "Validasi & Simpan"
    Then I should see the message "Resep obat dan rujukan lab berhasil disimpan."
    And the prescription and the referral should be saved in the same follow-up
    And the visit status should be "Rujukan Lab & Resep Tersimpan"
    And the medical record page should show the follow-up type "Resep Obat + Rujukan Lab"

  Scenario: Finish Visit Without Further Action
    Given the patient's SOAP draft has been verified
    When I check "Tanpa Tindak Lanjut"
    And I press "Validasi & Simpan"
    Then I should see the message "Rekam medis pasien telah ditandai selesai tanpa tindakan lanjutan."
    And no prescription or referral should be saved
    And the visit status should be "Selesai Tanpa Tindakan Lanjut"

  Scenario: "Tanpa Tindak Lanjut" Cannot Be Combined With Other Options
    Given the patient's SOAP draft has been verified
    When I send a follow-up with both "Buat E-Resep" and "Tanpa Tindak Lanjut" selected
    Then I should see the message "Lengkapi field wajib sebelum validasi."
    And I should see the error "Pilihan Tanpa Tindakan Lanjut tidak dapat digabung dengan resep atau rujukan lab."
    And no follow-up should be saved

  Scenario: E-Prescription Rejected When Medicine Data Is Incomplete
    Given the patient's SOAP draft has been verified
    When I check "Buat E-Resep"
    And I fill in a medicine without "Aturan Minum"
    And I press "Validasi & Simpan"
    Then I should see the message "Lengkapi field wajib sebelum validasi."
    And I should see the error "Aturan minum wajib diisi." on "Aturan Minum"
    And no partial prescription should be saved

  Scenario: Laboratory Referral Rejected Without Examination Parameter
    Given the patient's SOAP draft has been verified
    When I check "Buat Surat Rujukan Lab"
    And I fill in "Catatan Rujukan" with "Evaluasi fungsi ginjal." without selecting a parameter
    And I press "Validasi & Simpan"
    Then I should see the error "Parameter pemeriksaan laboratorium wajib dipilih."
    And no referral should be saved

  Scenario: Follow-up Cannot Be Saved Twice
    Given the patient's SOAP draft has been verified
    And I have already saved an e-prescription for this visit
    When I send another follow-up for the same visit
    Then I should receive an error response with status "409 Conflict"
    And I should see the message "Tindak lanjut untuk kunjungan ini sudah tersimpan."
    And the visit status should remain "Resep Tersimpan"

  Scenario: Finalized Medical Record Cannot Be Changed
    Given the patient's SOAP draft has been verified
    And the medical record has been finalized and locked
    When I send an e-prescription for this visit
    Then I should receive an error response with status "403 Forbidden"
    And I should see the message "Rekam medis sudah difinalisasi dan tidak dapat diubah."
    And no follow-up should be saved

  Scenario: Doctor From Another Hospital Is Blocked
    Given the patient's SOAP draft has been verified
    And another doctor is assigned to "RSUD Dr. Soetomo"
    When that doctor sends a follow-up for this patient's visit
    Then they should receive an error response with status "403 Forbidden"
    And the message should be "Akses ditolak: Anda tidak memiliki wewenang untuk mengakses rekam medis dari rumah sakit lain."
    And no follow-up should be saved
