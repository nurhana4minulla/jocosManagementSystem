<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }
include '../includes/header.php'; 

require_once '../includes/database.php';
$db = new Database();
$conn = $db->getConnection();

$draft_json = 'null';
$draft_id = 0;
if (isset($_GET['draft_id'])) {
    $draft_id = intval($_GET['draft_id']);
    $stmt = $conn->prepare("SELECT form_data FROM pds_drafts WHERE draft_id = ?");
    $stmt->bind_param("i", $draft_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $draft_json = $row['form_data']; 
    }
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            
           

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold" style="color: #0F172A;">Personal Data Sheet Entry</h5>
                    <span class="badge rounded-pill px-3 py-2" style="background-color: #0F172A;"><?php echo date('F j, Y'); ?></span>
                </div>
                
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between flex-wrap gap-2 mb-4 border-bottom pb-3">
                        <div id="L1" class="step-indicator fw-bold" style="color: #0F172A; cursor: pointer;" onclick="goToStep(1)">
                            <i class="bi bi-1-circle me-1"></i> Personal
                        </div>
                        <div id="L2" class="step-indicator text-muted" style="cursor: pointer;" onclick="goToStep(2)">
                            <i class="bi bi-2-circle me-1"></i> Family & IDs
                        </div>
                        <div id="L3" class="step-indicator text-muted" style="cursor: pointer;" onclick="goToStep(3)">
                            <i class="bi bi-3-circle me-1"></i> Educ & Elig.
                        </div>
                        <div id="L4" class="step-indicator text-muted" style="cursor: pointer;" onclick="goToStep(4)">
                            <i class="bi bi-4-circle me-1"></i> Contract & L&D
                        </div>
                    </div>

                    <div id="ajaxErrorMessage" class="alert alert-danger d-none shadow-sm mb-4" role="alert"></div>

                    <form action="save_employee.php" method="POST" id="pdsForm" enctype="multipart/form-data">
                        
                        <div class="form-step" id="S1">
                            <div class="row g-3 mb-4">
                                <div class="col-md-9">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Basic Information</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4"><label class="form-label small fw-bold" placeholder="Leave blank or N/A if none">Office ID No. <span class="text-muted fw-normal" style="font-size: 0.7rem;">leave blank or N/A if none</span> </label><input type="text" name="office_id" class="form-control" ></div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">First Name <span class="text-danger">*</span></label><input type="text" name="first_name" class="form-control" required></div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Last Name <span class="text-danger">*</span></label><input type="text" name="last_name" class="form-control" required></div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Middle Name</label><input type="text" name="middle_name" class="form-control"></div>
                                        <div class="col-md-2"><label class="form-label small fw-bold">Ext.</label><input type="text" name="name_extension" class="form-control" placeholder="Jr."></div>
                                        
                                        <div class="col-md-4"><label class="form-label small fw-bold">Date of Birth <span class="text-danger">*</span></label><input type="date" name="dob" id="dob" class="form-control" max="<?php echo date('Y-m-d'); ?>" onchange="calculateAge()" required></div>
                                        <div class="col-md-2"><label class="form-label small fw-bold text-muted">Age</label><input type="text" id="age_display" class="form-control bg-light" readonly></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">2x2 Photo</h6>
                                    <div class="border rounded p-2 text-center bg-light">
                                        <input type="file" name="photo" class="form-control form-control-sm" accept="image/jpeg, image/png">
                                        <small class="text-muted d-block mt-1">Optional. Max 2MB.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label small fw-bold">Place of Birth</label><input type="text" name="place_of_birth" class="form-control"></div>
                                <div class="col-md-2"><label class="form-label small fw-bold">Sex <span class="text-danger">*</span></label><select name="sex" class="form-select" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                                <div class="col-md-2"><label class="form-label small fw-bold">Civil Status <span class="text-danger">*</span></label><select name="civil_status" class="form-select" required><option value="">Select</option><option value="Single">Single</option><option value="Married">Married</option><option value="Widowed">Widowed</option><option value="Separated">Separated</option>
                                <option value="Others">Others</option></select></div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Citizenship <span class="text-danger">*</span></label>
                                    <select name="citizenship" id="citizenship" class="form-select" onchange="toggleCitizenship(this)" required>
                                        <option value="Filipino">Filipino</option>
                                        <option value="Dual Citizenship">Dual Citizenship</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Citizenship Type</label>
                                    <select name="citizenship_type" id="citizenship_type" class="form-select" disabled>
                                        <option value="">N/A</option>
                                        <option value="By Birth">By Birth</option>
                                        <option value="By Naturalization">By Naturalization</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Country/ies (If Dual)</label>
                                    <input type="text" name="citizenship_country" id="citizenship_country" class="form-control" placeholder="e.g. USA, UK" disabled>
                                </div>
                            </div>

                            <h6 class="fw-bold mt-4 mb-3 border-bottom pb-2">Demographics</h6>
                            <div class="row g-3 bg-light p-3 rounded border">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_indigenous" id="is_indigenous" value="1" onchange="document.getElementById('ig_name').disabled = !this.checked;">
                                        <label class="form-check-label small fw-bold">Indigenous Group Member</label>
                                    </div>
                                    <input type="text" name="ig_name" id="ig_name" class="form-control form-control-sm mt-1" placeholder="Specify group..." disabled>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_pwd" id="is_pwd" value="1" onchange="document.getElementById('pwd_id').disabled = !this.checked;">
                                        <label class="form-check-label small fw-bold">Person with Disability (PWD)</label>
                                    </div>
                                    <input type="text" name="pwd_id" id="pwd_id" class="form-control form-control-sm mt-1" placeholder="PWD ID No." disabled>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_solo_parent" id="is_solo_parent" value="1" onchange="document.getElementById('solo_parent_id').disabled = !this.checked;">
                                        <label class="form-check-label small fw-bold">Solo Parent</label>
                                    </div>
                                    <input type="text" name="solo_parent_id" id="solo_parent_id" class="form-control form-control-sm mt-1" placeholder="Solo Parent ID No." disabled>
                                </div>
                            </div>

                            <h6 class="fw-bold mt-4 mb-3 border-bottom pb-2">Contact & Address</h6>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label small fw-bold">Contact Number <span class="text-danger">*</span></label><input type="text" name="contact_number" class="form-control"  title="Please enter numbers only" required></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">Email Address</label><input type="email" name="email" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label small fw-bold">Blood Type</label><input type="text" name="blood_type" class="form-control"></div>

                                <div class="col-md-9">
                                    <label class="form-label small fw-bold">Residential Address <span class="text-muted fw-normal" style="font-size: 0.7rem;">(Street/House No., Barangay, City, Province)</span> <span class="text-danger">*</span></label>
                                    <input type="text" name="residential_address" id="res_address" class="form-control" required onkeyup="copyAddress()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Zip Code</label>
                                    <input type="text" name="residential_zip" id="res_zip" class="form-control" placeholder="e.g. 7000" onkeyup="copyAddress()">
                                </div>

                                <div class="col-12 mt-3">
                                    <div class="d-flex align-items-center mb-1">
                                        <label class="form-label small fw-bold mb-0 me-3">Permanent Address <span class="text-muted fw-normal" style="font-size: 0.7rem;">(Street/House No., Barangay, City, Province)</span></label>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" id="sameAddress" onchange="copyAddress()">
                                            <label class="form-check-label small text-muted" for="sameAddress">Same as Residential</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" name="permanent_address" id="perm_address" class="form-control mt-1">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="permanent_zip" id="perm_zip" class="form-control mt-1" placeholder="e.g. 7000" >
                                </div>

                            </div>
                        </div>

                        <div class="form-step d-none" id="S2">
                            <label class="form-label small fw-bold">Emergency Contact </label>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6"><label class="form-label small fw-bold">Name</label><input type="text" name="emergency_contact_name" class="form-control" ></div>
                                <div class="col-md-6"><label class="form-label small fw-bold">Number</label><input type="text" name="emergency_contact_number" class="form-control" ></div>
                            </div>

                            <h6 class="fw-bold mb-3 border-bottom pb-2">Parents & Spouse (Optional)</h6>
                            <div class="row g-2 mb-4">
                                <div class="col-12 text-muted small fw-bold mt-2">Spouse</div>
                                <div class="col-md-3"><input type="text" name="spouse_fname" class="form-control form-control-sm" placeholder="First Name"></div>
                                <div class="col-md-3"><input type="text" name="spouse_mname" class="form-control form-control-sm" placeholder="Middle Name"></div>
                                <div class="col-md-3"><input type="text" name="spouse_lname" class="form-control form-control-sm" placeholder="Last Name"></div>
                                <div class="col-md-3"><input type="text" name="spouse_ext" class="form-control form-control-sm" placeholder="Extension (Jr., Sr.)"></div>
                                
                                <div class="col-12 text-muted small fw-bold mt-2">Father</div>
                                <div class="col-md-3"><input type="text" name="father_fname" class="form-control form-control-sm" placeholder="First Name"></div>
                                <div class="col-md-3"><input type="text" name="father_mname" class="form-control form-control-sm" placeholder="Middle Name"></div>
                                <div class="col-md-3"><input type="text" name="father_lname" class="form-control form-control-sm" placeholder="Last Name"></div>
                                <div class="col-md-3"><input type="text" name="father_ext" class="form-control form-control-sm" placeholder="Extension (Jr., Sr.)"></div>

                                <div class="col-12 text-muted small fw-bold mt-2">Mother (Maiden Name)</div>
                                <div class="col-md-4"><input type="text" name="mother_fname" class="form-control form-control-sm" placeholder="First Name"></div>
                                <div class="col-md-4"><input type="text" name="mother_mname" class="form-control form-control-sm" placeholder="Middle Name"></div>
                                <div class="col-md-4"><input type="text" name="mother_lname" class="form-control form-control-sm" placeholder="Last Name"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                <h6 class="fw-bold mb-0">Children (Optional)</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addChild()">+ Add Child</button>
                            </div>
                            <div id="children_container" class="mb-4"></div>

                            <h6 class="fw-bold mb-3 border-bottom pb-2">Government IDs</h6>
                            <div class="row g-3">
                                <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text small fw-bold" style="width: 110px;">TIN</span><input type="text" name="id_tin" class="form-control"></div></div>
                                <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text small fw-bold" style="width: 110px;">UMID</span><input type="text" name="id_umid" class="form-control"></div></div>
                                <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text small fw-bold" style="width: 110px;">PhilHealth</span><input type="text" name="id_philhealth" class="form-control"></div></div>
                                <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text small fw-bold" style="width: 110px;">Pag-IBIG</span><input type="text" name="id_pagibig" class="form-control"></div></div>
                                <div class="col-md-6"><div class="input-group mb-2"><span class="input-group-text small fw-bold" style="width: 110px; font-size: 0.9rem;">PhilSys (PSN)</span><input type="text" name="id_philsys" class="form-control"></div></div>
                            </div>
                        </div>

                        <div class="form-step d-none" id="S3">
                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                <h6 class="text-primary fw-bold mb-0">Educational Background</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addEducation()">+ Add Education</button>
                            </div>
                            <div id="edu_container" class="mb-4">
                                <div class="p-3 border rounded bg-light mb-3 edu-row shadow-sm">
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-3"><label class="form-label small fw-bold">Level <span class="text-danger">*</span></label><select name="edu_level[]" class="form-select form-select-sm edu-level-select" required><option value="Elementary">Elementary</option><option value="Secondary">Secondary</option><option value="Vocational/Trade Course">Vocational/Trade</option><option value="College" selected>College</option><option value="Graduate Studies">Graduate Studies</option></select></div>
                                        <div class="col-md-3"><label class="form-label small fw-bold">School Name <span class="text-danger">*</span></label><input type="text" name="school_name[]" class="form-control form-control-sm" required></div>
                                        <div class="col-md-3"><label class="form-label small fw-bold">Degree / Course</label><input type="text" name="degree_course[]" class="form-control form-control-sm" placeholder="e.g. High School"></div>
                                        <div class="col-md-3"><label class="form-label small fw-bold">From (Year)</label><input type="text" name="start_year[]" class="form-control form-control-sm" maxlength="4" placeholder="YYYY"></div>
                                        
                                        <div class="col-md-3"><label class="form-label small fw-bold">To (Year)</label><input type="text" name="end_year[]" class="form-control form-control-sm" maxlength="4" placeholder="YYYY"></div>
                                        <div class="col-md-2"><label class="form-label small fw-bold">Graduated?</label><select name="is_graduated[]" class="form-select form-select-sm grad-select" onchange="checkGradStatus(this)"><option value="1">Yes</option><option value="0">No</option></select></div>
                                        <div class="col-md-2"><label class="form-label small fw-bold">Year Grad.</label><input type="text" name="year_graduated[]" class="form-control form-control-sm y-grad-input" maxlength="4" placeholder="YYYY"></div>
                                        <div class="col-md-2"><label class="form-label small fw-bold">Highest Units</label><input type="text" name="highest_level_units[]" class="form-control form-control-sm units-input" placeholder="N/A" readonly style="background-color: #e9ecef;"></div>
                                        <div class="col-md-3"><label class="form-label small fw-bold">Academic Honors</label><input type="text" name="academic_honors[]" class="form-control form-control-sm" placeholder="e.g. Cum Laude"></div>
                                    </div>
                                </div>
                            </div>
                        

                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                <h6 class="fw-bold mb-0">Civil Service Eligibility (Optional)</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEligibility()">+ Add Eligibility</button>
                            </div>
                            <div id="eligibility_container"></div>
                        </div>
<!-- start edit -->
                        <div class="form-step d-none" id="S4">
                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                <h6 class="text-primary fw-bold mb-0">Work Experience <span class="text-muted small fw-normal">(Leave empty if none)</span></h6>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addWork()">+ Add Work</button>
                            </div>
                            <div id="work_container" class="mb-4">
                                <div class="p-3 border rounded bg-light mb-3 work-row shadow-sm position-relative">
                                    <span class="badge bg-primary position-absolute top-0 start-0 translate-middle-y ms-3">Current / Most Recent</span>
                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4"><label class="form-label small fw-bold">Position Title</label><input type="text" name="position_title[]" class="form-control form-control-sm"></div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Department / Agency</label><input type="text" name="department_program[]" class="form-control form-control-sm dept-input"></div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Employment Type</label>
                                            <select name="employment_type_base[]" class="form-select form-select-sm emp-type-select" onchange="toggleDepartmentRow(this)">
                                                <option value="JO">Job Order (JO)</option>
                                                <option value="COS">Contract of Service (COS)</option>
                                                <option value="Others">Others</option>
                                            </select>
                                            <input type="text" name="employment_type_specify[]" class="form-control form-control-sm mt-1 specify-input d-none" placeholder="Please specify...">
                                        </div>
                                        <div class="col-md-3"><label class="form-label small fw-bold">Office Assignment</label><select name="office_assignment[]" class="form-select form-select-sm"><option value="Zamboanga City">Zamboanga City</option><option value="Zamboanga del Norte">Z. del Norte</option><option value="Zamboanga del Sur">Z. del Sur</option><option value="Zamboanga Sibugay">Z. Sibugay</option></select></div>
                                        <div class="col-md-3"><label class="form-label small fw-bold">Monthly Salary</label><input type="number" step="0.01" name="salary[]" class="form-control form-control-sm"></div>
                                        <div class="col-md-2"><label class="form-label small fw-bold">Start Date</label><input type="date" name="start_date[]" class="form-control form-control-sm"></div>
                                        <div class="col-md-2"><label class="form-label small fw-bold">End Date <small class="text-muted fw-normal">(Blank if Present)</small></label><input type="date" name="end_date[]" class="form-control form-control-sm"></div>
                                    </div>
                                </div>
                            </div>
 <!-- endddd  -->

                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                <h6 class="fw-bold mb-0">Learning and Development (L&D) Interventions</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTraining()">+ Add L&D</button>
                                </div>
                                <div id="ld_container" class="mb-4"></div> 
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2 mt-4">
                                    <h6 class="fw-bold mb-0">Other Information</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOtherInfo()">+ Add Info</button>
                                </div>
                                <div id="other_info_container" class="mb-4"></div>
                        </div>

                        <div class="mt-5 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary px-4 fw-bold" id="prevBtn" onclick="move(-1)" disabled>Previous</button>
                            <button type="button" class="btn btn-outline-secondary fw-bold px-4 me-2" id="draftBtn" onclick="saveToDatabaseDraft()">
                                <i class="bi bi-save"></i> Save as Draft
                            </button>
                            <button type="button" class="btn text-white px-4 fw-bold" style="background-color: #0F172A;" id="nextBtn" onclick="move(1)">Next Step</button>
                            <button type="submit" class="btn btn-success px-5 fw-bold d-none" id="saveBtn">Save Employee</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let step = 1;

function move(n) {
    if (n === 1) {
        const inputs = document.getElementById('S' + step).querySelectorAll('input, select');
        for (let i = 0; i < inputs.length; i++) {
            if (!inputs[i].checkValidity()) { inputs[i].reportValidity(); return; }
        }
    }
    document.getElementById('S' + step).classList.add('d-none');
    document.getElementById('L' + step).classList.remove('fw-bold');
    document.getElementById('L' + step).style.color = '';
    step += n;
    document.getElementById('S' + step).classList.remove('d-none');
    document.getElementById('L' + step).classList.add('fw-bold');
    document.getElementById('L' + step).style.color = '#0F172A';
    document.getElementById('prevBtn').disabled = (step === 1);
    document.getElementById('nextBtn').classList.toggle('d-none', step === 4);
    document.getElementById('saveBtn').classList.toggle('d-none', step !== 4);
}

function goToStep(newStep) {
    // 1. Hide the current step
    document.getElementById('S' + step).classList.add('d-none');
    document.getElementById('L' + step).classList.remove('fw-bold');
    document.getElementById('L' + step).style.color = '';
    document.getElementById('L' + step).classList.add('text-muted');

    // 2. Update the step variable to the clicked step
    step = newStep;

    // 3. Show the new step
    document.getElementById('S' + step).classList.remove('d-none');
    document.getElementById('L' + step).classList.add('fw-bold');
    document.getElementById('L' + step).style.color = '#0F172A';
    document.getElementById('L' + step).classList.remove('text-muted');

    // 4. Update the Previous/Next/Save buttons
    document.getElementById('prevBtn').disabled = (step === 1);
    document.getElementById('nextBtn').classList.toggle('d-none', step === 4);
    
    const saveBtn = document.getElementById('saveBtn');
    if(saveBtn) {
        saveBtn.classList.toggle('d-none', step !== 4);
    }
}



function calculateAge() {
    let dob = document.getElementById('dob').value;
    if(!dob) return;
    let today = new Date();
    let birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    let m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) { age--; }
    document.getElementById('age_display').value = age;
}

// Function to lock the Department input if JO is selected
function toggleDepartmentRow(sel) {
    const row = sel.closest('.work-row');
    const deptInput = row.querySelector('.dept-input');
    const specifyInput = row.querySelector('.specify-input');
    
    if (sel.value === 'Others') {
        specifyInput.classList.remove('d-none');
        specifyInput.setAttribute('required', 'true');
    } else {
        specifyInput.classList.add('d-none');
        specifyInput.removeAttribute('required');
    }
    
    if (sel.value === 'JO') {
        deptInput.value = 'N/A';
        deptInput.setAttribute('readonly', true);
        deptInput.style.backgroundColor = '#e2e8f0'; 
    } else {
        deptInput.removeAttribute('readonly');
        if(deptInput.value === 'N/A') deptInput.value = '';
        deptInput.style.backgroundColor = ''; 
    }
}

function copyAddress() {
    let res = document.getElementById('res_address').value;
    let resZip = document.getElementById('res_zip').value;
    let perm = document.getElementById('perm_address');
    let permZip = document.getElementById('perm_zip');
    
    if(document.getElementById('sameAddress').checked) { 
        perm.value = res; perm.readOnly = true; 
        permZip.value = resZip; permZip.readOnly = true;
    } else { 
        perm.readOnly = false; 
        permZip.readOnly = false;
    }
}

function addChild() {
    let html = `<div class="row g-2 mb-2 child-row">
        <div class="col-md-4"><input type="text" name="child_fname[]" class="form-control form-control-sm" placeholder="First Name"></div>
        <div class="col-md-3"><input type="text" name="child_lname[]" class="form-control form-control-sm" placeholder="Last Name"></div>
        <div class="col-md-4"><input type="date" name="child_dob[]" class="form-control form-control-sm" max="<?php echo date('Y-m-d'); ?>" onchange="calculateAge()" ></div>
        <div class="col-md-1"><button type="button" class="btn btn-sm btn-glass-danger w-100 fw-bold shadow-sm" onclick="this.closest('.child-row').remove()" title="Remove Child"><i class="bi bi-trash3"></i></button></div>
    </div>`;
    document.getElementById('children_container').insertAdjacentHTML('beforeend', html);
}

function addEducation() {
    let html = `<div class="p-3 border rounded bg-light mb-3 edu-row shadow-sm"><div class="row g-3 mt-1">
        <div class="col-md-3"><label class="form-label small fw-bold">Level <span class="text-danger">*</span></label><select name="edu_level[]" class="form-select form-select-sm edu-level-select" required><option value="Elementary">Elementary</option><option value="Secondary">Secondary</option><option value="Vocational/Trade Course">Vocational/Trade</option><option value="College" selected>College</option><option value="Graduate Studies">Graduate Studies</option></select></div>
        <div class="col-md-3"><label class="form-label small fw-bold">School Name <span class="text-danger">*</span></label><input type="text" name="school_name[]" class="form-control form-control-sm" required></div>
        <div class="col-md-3"><label class="form-label small fw-bold">Degree / Course</label><input type="text" name="degree_course[]" class="form-control form-control-sm" placeholder="e.g. High School"></div>
        <div class="col-md-3"><label class="form-label small fw-bold">From (Year)</label><input type="text" name="start_year[]" class="form-control form-control-sm" maxlength="4" placeholder="YYYY"></div>
        <div class="col-md-3"><label class="form-label small fw-bold">To (Year)</label><input type="text" name="end_year[]" class="form-control form-control-sm" maxlength="4" placeholder="YYYY"></div>
        <div class="col-md-2"><label class="form-label small fw-bold">Graduated?</label><select name="is_graduated[]" class="form-select form-select-sm grad-select" onchange="checkGradStatus(this)"><option value="1">Yes</option><option value="0">No</option></select></div>
        <div class="col-md-2"><label class="form-label small fw-bold">Year Grad.</label><input type="text" name="year_graduated[]" class="form-control form-control-sm y-grad-input" maxlength="4" placeholder="YYYY"></div>
        <div class="col-md-2"><label class="form-label small fw-bold">Highest Units</label><input type="text" name="highest_level_units[]" class="form-control form-control-sm units-input" placeholder="N/A" readonly style="background-color: #e9ecef;"></div>
        <div class="col-md-3"><label class="form-label small fw-bold">Academic Honors</label><input type="text" name="academic_honors[]" class="form-control form-control-sm" placeholder="e.g. Cum Laude"></div>
        <div class="col-md-12 text-end mt-2"><button type="button" class="btn btn-sm btn-glass-danger fw-bold shadow-sm px-4" onclick="this.closest('.edu-row').remove()"><i class="bi bi-trash3 me-1"></i> Remove</button></div>
    </div></div>`;
    document.getElementById('edu_container').insertAdjacentHTML('beforeend', html);
}

function addEligibility() {
    let html = `<div class="p-3 border rounded bg-light mb-2 elig-row shadow-sm">
        <div class="row g-2 align-items-end">
            <div class="col-md-4"><label class="small text-muted mb-1">Eligibility Name</label><input type="text" name="eligibility_name[]" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="small text-muted mb-1">Rating</label><input type="text" step="0.01" name="eligibility_rating[]" placeholder="e.g. 85.5%" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="small text-muted mb-1">Exam/Conferment Date</label><input type="date" name="date_of_exam_conferment[]" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="small text-muted mb-1">Valid Until Date <span class="text-muted fw-normal" style="font-size: 0.7rem;">(Leave blank if Non-Expiry)</span></label><input type="date" name="valid_until[]" class="form-control form-control-sm"></div>
            <div class="col-md-4"><label class="small text-muted mb-1 mt-2">Place of Exam/Conferment</label><input type="text" name="place_of_exam_conferment[]" class="form-control form-control-sm"></div>
            <div class="col-md-5"><label class="small text-muted mb-1 mt-2">License Number</label><input type="text" name="license_number[]" class="form-control form-control-sm"></div>
            <div class="col-md-3"><button type="button" class="btn btn-sm btn-glass-danger w-100 mt-4 fw-bold shadow-sm" onclick="this.closest('.elig-row').remove()"><i class="bi bi-trash3 me-1"></i> Remove</button></div>
        </div>
    </div>`;
    document.getElementById('eligibility_container').insertAdjacentHTML('beforeend', html);
}

function addTraining() {
    let html = `<div class="p-3 border rounded bg-light mb-2 train-row shadow-sm">
        <div class="row g-2 align-items-end">
            <div class="col-md-12"><label class="small text-muted mb-1">Title of L&D Program</label><input type="text" name="training_title[]" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="small text-muted mb-1 mt-2">Inclusive Date From</label><input type="date" name="train_start[]" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="small text-muted mb-1 mt-2">Inclusive Date To</label><input type="date" name="train_end[]" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="small text-muted mb-1 mt-2">Total Hours</label><input type="number" name="training_hours[]" class="form-control form-control-sm"></div>
            <div class="col-md-4"><label class="small text-muted mb-1 mt-2">Type (e.g. Managerial)</label><input type="text" name="l_and_d_type[]" class="form-control form-control-sm"></div>
            <div class="col-md-9"><label class="small text-muted mb-1 mt-2">Conducted / Sponsored By</label><input type="text" name="sponsor[]" class="form-control form-control-sm"></div>
            <div class="col-md-3"><button type="button" class="btn btn-sm btn-glass-danger w-100 mt-4 fw-bold shadow-sm" onclick="this.closest('.train-row').remove()"><i class="bi bi-trash3 me-1"></i> Remove</button></div>
        </div>
    </div>`;
    document.getElementById('ld_container').insertAdjacentHTML('beforeend', html);
}

function addWork() {
    let html = `<div class="p-3 border rounded bg-light mb-3 work-row shadow-sm"><div class="row g-3 mt-1">
        <div class="col-md-4"><label class="form-label small fw-bold">Position Title</label><input type="text" name="position_title[]" class="form-control form-control-sm"></div>
        <div class="col-md-4"><label class="form-label small fw-bold">Department / Agency</label><input type="text" name="department_program[]" class="form-control form-control-sm dept-input"></div>
        <div class="col-md-4"><label class="form-label small fw-bold">Employment Type</label><select name="employment_type_base[]" class="form-select form-select-sm emp-type-select" onchange="toggleDepartmentRow(this)"><option value="JO">Job Order (JO)</option><option value="COS">Contract of Service (COS)</option><option value="Others">Others</option></select><input type="text" name="employment_type_specify[]" class="form-control form-control-sm mt-1 specify-input d-none" placeholder="Please specify..."></div>
        <div class="col-md-3"><label class="form-label small fw-bold">Office Assignment</label><select name="office_assignment[]" class="form-select form-select-sm"><option value="Zamboanga City">Zamboanga City</option><option value="Zamboanga del Norte">Z. del Norte</option><option value="Zamboanga del Sur">Z. del Sur</option><option value="Zamboanga Sibugay">Z. Sibugay</option></select></div>
        <div class="col-md-3"><label class="form-label small fw-bold">Monthly Salary</label><input type="number" step="0.01" name="salary[]" class="form-control form-control-sm"></div>
        <div class="col-md-2"><label class="form-label small fw-bold">Start Date</label><input type="date" name="start_date[]" class="form-control form-control-sm"></div>
        <div class="col-md-2"><label class="form-label small fw-bold">End Date <small class="text-muted fw-normal">(Blank if Present)</small></label><input type="date" name="end_date[]" class="form-control form-control-sm"></div>
        <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-sm btn-glass-danger w-100 fw-bold shadow-sm" onclick="this.closest('.work-row').remove()"><i class="bi bi-trash3 me-1"></i> Remove</button></div>
    </div></div>`;
    document.getElementById('work_container').insertAdjacentHTML('beforeend', html);
    const newRow = document.getElementById('work_container').lastElementChild;
    toggleDepartmentRow(newRow.querySelector('.emp-type-select'));
}

function toggleEdu(sel) { 
    const row = sel.closest('.edu-row');
    row.querySelector('.strand-div').style.display = (sel.value === 'Senior High School') ? 'block' : 'none'; 
    const degreeInput = row.querySelector('input[name="degree_course[]"]');
    if (sel.value === 'Elementary' || sel.value === 'Secondary' || sel.value === 'Senior High School') {
        degreeInput.readOnly = true;             
        degreeInput.value = '';                  
        degreeInput.style.backgroundColor = '#e9ecef'; 
        degreeInput.placeholder = "N/A";
    } else {
        degreeInput.readOnly = false;
        degreeInput.style.backgroundColor = '';
        degreeInput.placeholder = "Degree/Course";
    }
}

function checkGradStatus(sel) {
    const row = sel.closest('.edu-row');
    const yGrad = row.querySelector('.y-grad-input');
    const units = row.querySelector('.units-input');

    if(sel.value === "0") { // 0 = Undergraduate
        // Disable Year Graduated
        yGrad.readOnly = true;
        yGrad.value = "";
        yGrad.required = false; 
        yGrad.style.backgroundColor = '#e9ecef';
        yGrad.placeholder = "N/A";
        
        // Enable Units Earned
        if(units) {
            units.readOnly = false;
            units.style.backgroundColor = '';
            units.placeholder = "e.g. 72 units / 3rd Yr";
        }
    } else { // 1 = Graduated
        // Enable Year Graduated
        yGrad.readOnly = false;
        yGrad.required = true;  
        yGrad.style.backgroundColor = '';
        yGrad.placeholder = "Year";
        
        // Disable Units Earned
        if(units) {
            units.readOnly = true;
            units.value = "";
            units.style.backgroundColor = '#e9ecef';
            units.placeholder = "N/A";
        }
    }
}

function addOtherInfo() {
    let html = `<div class="p-2 border rounded bg-light mb-2 other-row shadow-sm">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="small text-muted mb-1">Category</label>
                <select name="detail_type[]" class="form-select form-select-sm" required>
                    <option value="">Select Category...</option>
                    <option value="Skill">Special Skills & Hobbies</option>
                    <option value="Distinction">Non-Academic Distinctions / Recognitions</option>
                    <option value="Membership">Membership in Association / Organization</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="small text-muted mb-1">Details</label>
                <input type="text" name="detail_description[]" class="form-control form-control-sm" placeholder="Specify details..." required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-glass-danger w-100 mt-4 fw-bold shadow-sm" onclick="this.closest('.other-row').remove()"><i class="bi bi-trash3 me-1"></i> Remove</button>
            </div>
        </div>
    </div>`;
    document.getElementById('other_info_container').insertAdjacentHTML('beforeend', html);
}

function toggleCitizenship(sel) {
    const typeSelect = document.getElementById('citizenship_type');
    const countryInput = document.getElementById('citizenship_country');
    if (sel.value === 'Dual Citizenship') {
        typeSelect.disabled = false;
        countryInput.disabled = false;
        typeSelect.required = true;
        countryInput.required = true;
    } else {
        typeSelect.disabled = true;
        typeSelect.value = '';
        typeSelect.required = false;
        countryInput.disabled = true;
        countryInput.value = '';
        countryInput.required = false;
    }
}

// calculate age and check JO status 
window.onload = function() {
    calculateAge();
    document.querySelectorAll('.emp-type-select').forEach(sel => toggleDepartmentRow(sel));
};

document.getElementById('pdsForm').addEventListener('submit', function(e) {
    e.preventDefault(); 
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

    let formData = new FormData(this);

    fetch('save_employee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            formChanged = false; // added new

            if (activeDraftId > 0) {
                fetch('delete_draft.php?id=' + activeDraftId);
            }
            
            window.location.href = 'add_employee.php?success=1';
        } else {
            const errorDiv = document.getElementById('ajaxErrorMessage');
            errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Hold on!</strong> ' + data.message;
            errorDiv.classList.remove('d-none');

            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Save & Hire Employee';

            if (data.field === 'office_id') {
                const badInput = document.querySelector('input[name="office_id"]');
                badInput.classList.add('is-invalid'); 
                
                document.getElementById('S' + step).classList.add('d-none');
                document.getElementById('L' + step).classList.remove('fw-bold');
                document.getElementById('L' + step).style.color = '';
                
                step = 1; 
                
                document.getElementById('S' + step).classList.remove('d-none');
                document.getElementById('L' + step).classList.add('fw-bold');
                document.getElementById('L' + step).style.color = '#0F172A';
                
                document.getElementById('prevBtn').disabled = true;
                document.getElementById('nextBtn').classList.remove('d-none');
                document.getElementById('saveBtn').classList.add('d-none');

                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                badInput.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    errorDiv.classList.add('d-none');
                });
            }
        }
    })
    .catch(error => {
        const errorDiv = document.getElementById('ajaxErrorMessage');
        errorDiv.innerHTML = '<strong>Network Error:</strong> Could not connect to the database.';
        errorDiv.classList.remove('d-none');
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Save & Hire Employee';
    });
}); 

let formChanged = false;

document.querySelectorAll('input, select, textarea').forEach(element => {
    element.addEventListener('change', () => {
        formChanged = true;
    });
});

window.addEventListener('beforeunload', function (e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

function saveToDatabaseDraft() {
    const draftBtn = document.getElementById('draftBtn');
    const originalText = draftBtn.innerHTML;
    
    draftBtn.disabled = true;
    draftBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

    const pdsForm = document.getElementById('pdsForm');
    const formData = new FormData(pdsForm);

    fetch('save_draft.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        draftBtn.disabled = false;
        draftBtn.innerHTML = originalText;
        
        if (data.success) {
            showToast("Success! Your draft has been safely stored.", "success");
            formChanged = false; 
        } else {
            alert("Error saving draft: " + data.message);
        }
    })
    .catch(error => {
        draftBtn.disabled = false;
        draftBtn.innerHTML = originalText;
        alert("Network error: Could not connect to the draft server.");
    });
}

const draftData = <?php echo $draft_json; ?>;
const activeDraftId = <?php echo $draft_id; ?>;

if (draftData) {
    for (const key in draftData) {
        const val = draftData[key];
        
        if (Array.isArray(val)) {
            const inputs = document.querySelectorAll(`[name="${key}[]"]`);
            val.forEach((v, index) => {
                if (inputs[index]) inputs[index].value = v;
            });
        } else {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = (val === 'on' || val == 1 || val == true);
                } else if (input.type === 'radio') {
                    const radio = document.querySelector(`[name="${key}"][value="${val}"]`);
                    if(radio) radio.checked = true;
                } else {
                    input.value = val;
                }
            }
        }
    }
    
    if (document.getElementById('sameAddress') && document.getElementById('sameAddress').checked) {
        copyAddress();
    }
    
   setTimeout(() => {
        showToast("Draft loaded successfully! Continue where you left off.", "success");
    }, 500);
}

document.addEventListener("DOMContentLoaded", function() {
    
    const saveBtn = document.getElementById('saveBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function(e) {
            const form = this.closest('form');
            
            if (!form.checkValidity()) {
                e.preventDefault(); 
                
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    const stepDiv = firstInvalid.closest('div[id^="S"]');
                    
                    if (stepDiv) {
                        const stepNum = parseInt(stepDiv.id.replace('S', ''));
                        
                        if (typeof goToStep === "function") {
                            goToStep(stepNum);
                        }
                        
                        showToast('Missing required field. Please fill out the highlighted box.', 'danger');
                        
                        firstInvalid.classList.add('is-invalid');

                        setTimeout(() => firstInvalid.focus(), 150);
                        
                        firstInvalid.addEventListener('input', function() {
                            this.classList.remove('is-invalid');
                        }, { once: true });
                    }
                }
            }
        });
    }
});

</script>

<?php include '../includes/footer.php'; ?>