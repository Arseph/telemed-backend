<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProfileCtrl;
use App\Http\Controllers\SampleCtrl;
use App\Http\Controllers\ExitInterviewctrl;
use App\Http\Controllers\Manage\DtrController;
use App\Http\Controllers\Leave\LeaveCtrl;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\Dtr\DtrCtrl;

use App\Http\Controllers\Event\EventCtrl;
use App\Http\Controllers\Auth\PwResetCtrl;
use App\Http\Controllers\PdsCtrl;
use App\Http\Controllers\PassSlipCtrl;
use App\Http\Controllers\Coc\CocApplicationController;
use App\Http\Controllers\BestCan\BestCanController;
use App\Http\Controllers\AttendanceCtrl;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Superadmin\HomeController as SuperadminHomeController;
use App\Http\Controllers\Superadmin\ManageController as SuperadminManageController;
use App\Http\Controllers\Superadmin\DiagnosisController as SuperadminDiagnosisController;
use App\Http\Controllers\Superadmin\DrugsMedsCtrl;
use App\Http\Controllers\Superadmin\DocumentCtrl;
use App\Http\Controllers\Superadmin\LabRequestCtrl;
use App\Http\Controllers\Superadmin\AuditTrailCtrl;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\ManageController as AdminManageController;
use App\Http\Controllers\Admin\TeleController as AdminTeleController;
use App\Http\Controllers\Doctor\HomeController as DoctorHomeController;
use App\Http\Controllers\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Doctor\TeleConsultController as DoctorTeleConsultController;
use App\Http\Controllers\Doctor\ManageController as DoctorManageController;
use App\Http\Controllers\Doctor\IssueConcernCtrl;
use App\Http\Controllers\Patient\HomeController as PatientHomeController;
use App\Http\Controllers\Patient\PatientController as PatientPatientController;
use App\Http\Controllers\Tele\TeleController;
use App\Http\Controllers\Notification\NotiFController;
use App\Http\Controllers\FeedbackCtrl;

Route::group(['prefix' => 'auth'], function () {
  Route::post('dbms/login', [AuthController::class, 'dbmslogin']);
  Route::post('password/email', [PwResetCtrl::class, 'sendResetLink']);
  Route::post('password/reset', [PwResetCtrl::class, 'resetPassword']);
  Route::post('login', [AuthController::class, 'login']);
  Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('validate-token', [AuthController::class, 'validateToken']);
    Route::get('users', [AuthController::class, 'users']);
    Route::get('dtr', [DtrController::class, 'dtr']);
  });
});
Route::group(['prefix' => 'employee'], function () {
  Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('me', [ProfileCtrl::class, 'me']);
  });
});

Route::group(['middleware' => 'auth:sanctum'], function () {
   Route::post('register-account', [LoginController::class, 'register']);
    Route::get('/places/{id}/{type}', [LoginController::class, 'getMunandBrgy']);
    Route::get('/get-doctor/{id}', [LoginController::class, 'getDoctor']);
    Route::get('/validate-email', [LoginController::class, 'validateEmail']);
    Route::get('/validate-username', [LoginController::class, 'validateUsername']);
    Route::post('change-password', [LoginController::class, 'changePassword']);

    // Superadmin Module
    Route::get('superadmin', [SuperadminHomeController::class, 'index']);
    Route::get('/users', [SuperadminManageController::class, 'indexUser']);
    Route::post('/user-deactivate/{id}', [SuperadminManageController::class, 'deactivateUser']);
    Route::post('/user-store', [SuperadminManageController::class, 'storeUser']);
    Route::get('/facilities', [SuperadminManageController::class, 'indexFacility']);
    Route::get('/facilities/{id}/{type}', [SuperadminManageController::class, 'getMunandBrgy']);
    Route::post('/facility-store', [SuperadminManageController::class, 'storeFacility']);
    Route::post('/facility-delete/{id}', [SuperadminManageController::class, 'deleteFacility']);
    Route::get('/provinces', [SuperadminManageController::class, 'indexProvince']);
    Route::post('/province-store', [SuperadminManageController::class, 'storeProvince']);
    Route::post('/province-delete/{id}', [SuperadminManageController::class, 'deleteProvince']);
    Route::match(['GET','POST'],'/municipality/{province_id}/{province_name}', [SuperadminManageController::class, 'viewMunicipality']);
    Route::post('/municipality-store', [SuperadminManageController::class, 'storeMunicipality']);
    Route::post('/municipality-delete/{id}', [SuperadminManageController::class, 'deleteMunicipality']);
    Route::match(['GET','POST'],'/barangay/{prov_id}/{prov_name}/{mun_id}/{mun_name}', [SuperadminManageController::class, 'viewBarangay']);
    Route::post('/barangay-store', [SuperadminManageController::class, 'storeBarangay']);
    Route::post('/barangay-delete/{id}', [SuperadminManageController::class, 'deleteBarangay']);
    Route::match(['GET','POST'],'/diagnosis', [SuperadminDiagnosisController::class, 'indexDiagnosis']);
    Route::get('/diagnosis/{id}/maincat', [SuperadminDiagnosisController::class, 'getSubCategory']);
    Route::post('/superadmin-diagnosis-store', [SuperadminDiagnosisController::class, 'storeDiagnosis']);
    Route::post('/diagnosis-delete/{id}', [SuperadminDiagnosisController::class, 'deleteDiagnosis']);
    Route::match(['GET','POST'],'/diagnosis-main-category', [SuperadminDiagnosisController::class, 'indexDiagMainCat']);
    Route::post('/main-cat-store', [SuperadminDiagnosisController::class, 'storeMainCat']);
    Route::post('/main-cat-delete/{id}', [SuperadminDiagnosisController::class, 'deleteMainCat']);
    Route::match(['GET','POST'],'/diagnosis-sub-category', [SuperadminDiagnosisController::class, 'indexDiagSubCat']);
    Route::post('/sub-cat-store', [SuperadminDiagnosisController::class, 'storeSubCat']);
    Route::post('/sub-cat-delete/{id}', [SuperadminDiagnosisController::class, 'deleteSubCat']);
    Route::get('/doctor-option/{id}', [SuperadminManageController::class, 'getDoctors']);
    Route::get('/audit-trail', [SuperadminManageController::class, 'indexAudit']);
    Route::get('/doctor-category', [SuperadminManageController::class, 'indexTeleCat']);
    Route::post('/doctor-category-store', [SuperadminManageController::class, 'storeDoccat']);
    Route::post('/doctor-category-delete/{id}', [SuperadminManageController::class, 'deleteDoccat']);
    Route::post('/zoom-credential', [SuperadminManageController::class, 'zoomCredit']);

    // Admin Module
    Route::get('admin/support', [AdminHomeController::class, 'index']);
    Route::get('/admin-facility', [AdminManageController::class, 'AdminFacility']);
    Route::post('/update-facility', [AdminManageController::class, 'updateFacility']);
    Route::match(['GET','POST'],'/admin-patient', [AdminManageController::class, 'patientList']);
    Route::get('/admin-patient-meeting-info', [AdminManageController::class, 'meetingInfo']);
    Route::get('/admin-join-meeting', [AdminTeleController::class, 'joinMeeting']);
    Route::get('/admin-doctors', [AdminManageController::class, 'indexDoctors']);

    // Doctor Module
    Route::get('doctor', [DoctorHomeController::class, 'index']);
    Route::get('home/chart', [DoctorHomeController::class, 'chart']);
    Route::match(['GET','POST'],'doctor/patient/list', [DoctorPatientController::class, 'patientList']);
    Route::match(['GET','POST'],'doctor/patient/update', [DoctorPatientController::class, 'patientUpdate']);
    Route::get('location/barangay/{muncity_id}', [DoctorPatientController::class, 'getBaranggays']);
    Route::match(['GET','POST'],'/patient-store', [DoctorPatientController::class, 'storePatient']);
    Route::post('/patient-delete/{id}', [DoctorPatientController::class, 'deletePatient']);
    Route::get('/patient-information/{id}', [DoctorPatientController::class, 'patientInformation']);
    Route::post('/webex-token', [DoctorTeleConsultController::class, 'storeToken']);
    Route::post('/patient-accept/{id}', [DoctorPatientController::class, 'acceptPatient']);
    Route::post('/patient-consult-info/{id}', [DoctorPatientController::class, 'patientConsultInfo']);
    Route::get('/tele-details', [DoctorPatientController::class, 'teleDetails']);
    Route::match(['GET','POST'],'doctor/prescription', [DoctorManageController::class, 'prescription']);
    Route::post('/prescription-store', [DoctorManageController::class, 'prescriptionStore']);
    Route::post('/prescription-delete/{id}', [DoctorManageController::class, 'prescriptionDelete']);
    Route::match(['GET','POST'],'doctor/order', [DoctorManageController::class, 'doctorOrder']);
    Route::post('/docorder-store', [DoctorManageController::class, 'doctorOrderStore']);
    Route::post('/docorder-delete/{id}', [DoctorManageController::class, 'docorderDelete']);
    Route::post('/medical-history-store', [DoctorPatientController::class, 'medHisStore']);
    Route::get('/medical-history-info', [DoctorPatientController::class, 'medHisData']);
    Route::get('/get-patient-eref', [DoctorPatientController::class, 'getPatientEref']);
    Route::post('/doc-cat-complete-profile', [DoctorManageController::class, 'doccatcomplete']);

    // Patient Module
    Route::get('patient', [PatientHomeController::class, 'index']);
    Route::get('/patient/clinical/{id}', [PatientPatientController::class, 'clinical']);
    Route::post('/clinical-store', [PatientPatientController::class, 'clinicalStore']);
    Route::get('/patient/covid/{id}', [PatientPatientController::class, 'covid']);
    Route::post('/covid-store', [PatientPatientController::class, 'covidStore']);
    Route::post('/assess-store', [PatientPatientController::class, 'assessStore']);
    Route::get('/patient/diagnosis/{id}', [PatientPatientController::class, 'diagnosis']);
    Route::post('/diagnosis-store', [PatientPatientController::class, 'diagnosisStore']);
    Route::get('/patient/plan/{id}', [PatientPatientController::class, 'plan']);
    Route::post('/plan-store', [PatientPatientController::class, 'planStore']);
    Route::post('/demographic-store', [PatientPatientController::class, 'demographicStore']);
    Route::post('/physical-exam-store', [PatientPatientController::class, 'phyExamStore']);
    Route::get('/clinical-info', [PatientPatientController::class, 'clinicalInfo']);

    // JM superadmin Drugs/Meds
    Route::get('drugsmeds/', [DrugsMedsCtrl::class, 'index']);
    Route::post('drugsmeds/', [DrugsMedsCtrl::class, 'index']);
    Route::post('drugmeds/drugsmeds_body', [DrugsMedsCtrl::class, 'drugsmedsBody']);
    Route::post('drugsmeds/drugsmeds/delete', [DrugsMedsCtrl::class, 'drugsmedsDelete']);
    Route::post('drugsmeds/drugsmeds/add', [DrugsMedsCtrl::class, 'drugsmedsOptions']);
    Route::get('drugsmeds/unitofmes', [DrugsMedsCtrl::class, 'unitofmesIndex']);
    Route::post('drugsmeds/unitofmes', [DrugsMedsCtrl::class, 'unitofmesIndex']);
    Route::post('drugmeds/unitofmes_body', [DrugsMedsCtrl::class, 'unitofmesBody']);
    Route::post('drugsmeds/unitofmes/add', [DrugsMedsCtrl::class, 'unitofmesOptions']);
    Route::post('drugmeds/unitofmes/delete', [DrugsMedsCtrl::class, 'unitofmesDelete']);
    Route::get('drugsmeds/subcategory', [DrugsMedsCtrl::class, 'subcatIndex']);
    Route::post('drugsmeds/subcategory', [DrugsMedsCtrl::class, 'subcatIndex']);
    Route::post('drugmeds/subcat_body', [DrugsMedsCtrl::class, 'subcatBody']);
    Route::post('drugsmeds/subcat/add', [DrugsMedsCtrl::class, 'subcatOptions']);
    Route::post('drugsmeds/subcat/delete', [DrugsMedsCtrl::class, 'subcatDelete']);

    // Document type
    Route::get('document/type', [DocumentCtrl::class, 'index']);
    Route::post('document/type', [DocumentCtrl::class, 'index']);
    Route::post('superadmin/doc_type/body', [DocumentCtrl::class, 'doctypeBody']);
    Route::post('superadmin/doc_type/add', [DocumentCtrl::class, 'doctypeOptions']);
    Route::post('superadmin/doc_type/delete', [DocumentCtrl::class, 'doctypeDelete']);

    // Lab Request
    Route::get('superadmin/lab_request', [LabRequestCtrl::class, 'index']);
    Route::post('superadmin/lab_request', [LabRequestCtrl::class, 'index']);
    Route::post('superadmin/lab_request/body', [LabRequestCtrl::class, 'labrequestBody']);
    Route::post('superadmin/lab_request/add', [LabRequestCtrl::class, 'labrequestOptions']);
    Route::post('superadmin/lab_request/delete', [LabRequestCtrl::class, 'labrequestDelete']);

    // Audit Trail
    Route::get('superadim/audit-trail', [AuditTrailCtrl::class, 'index']);
    Route::post('superadim/audit-trail', [AuditTrailCtrl::class, 'index']);

    // Feedback
    Route::match(['get','post'] ,'feedback', [FeedbackCtrl::class, 'index']);
    Route::match(['get','post'] ,'feedback/view', [FeedbackCtrl::class, 'view']);
    Route::match(['get','post'] ,'superadmin/feedback', [FeedbackCtrl::class, 'sindex']);
    Route::post('superadmin/sfeedback_body', [FeedbackCtrl::class, 'sindexBody']);
    Route::post('superadmin/feedback/response', [FeedbackCtrl::class, 'sfeedbackResponse']);

    // Issue and Concern
    Route::match(['get','post'] ,'doctor/issuesconcern', [IssueConcernCtrl::class, 'index']);
    Route::get('issue/concern/{meet_id}/{issue_from}', [IssueConcernCtrl::class, 'IssueAndConcern']);
    Route::post('issue/concern/submit', [IssueConcernCtrl::class, 'issueSubmit']);

    // Teleconsult
    Route::get('/start-zoom-meeting', [DoctorTeleConsultController::class, 'zoomMeeting']);
    Route::get('/getToken', [TeleController::class, 'zoomToken']);
    Route::match(['GET','POST'],'/teleconsultation', [TeleController::class, 'index']);
    Route::match(['GET','POST'],'/sched-pending', [TeleController::class, 'schedTeleStore']);
    Route::get('/join-meeting/{id}', [TeleController::class, 'indexCall']);
    Route::get('/start-meeting/{id}', [TeleController::class, 'indexCall']);
    Route::get('/validate-datetime', [TeleController::class, 'validateDateTime']);
    Route::get('/admin-meeting-info', [TeleController::class, 'adminMeetingInfo']);
    Route::get('/meeting-info', [TeleController::class, 'meetingInfo']);
    Route::get('/get-pending-meeting/{id}', [TeleController::class, 'getPendingMeeting']);
    Route::post('/accept-decline-meeting/{id}', [TeleController::class, 'acceptDeclineMeeting']);
    Route::get('/doctor-order-info', [TeleController::class, 'getDocOrder']);
    Route::post('/lab-request-doctor-order', [TeleController::class, 'labreqStore']);
    Route::get('/refresh-token', [TeleController::class, 'refreshToken']);
    Route::get('/thank-you-page', [TeleController::class, 'thankYouPage']);
    Route::get('/calendar-meetings', [TeleController::class, 'calendarMeetings']);
    Route::get('/my-calendar-meetings', [TeleController::class, 'mycalendarMeetings']);
    Route::get('/get-doctors-facility', [TeleController::class, 'getDoctorsFacility']);
    Route::get('/teleconsultation/details/{id}', [TeleController::class, 'teleconsultDetails']);
    Route::post('/accept-notif-meeting', [TeleController::class, 'acceptNotifMeeting']);
    Route::post('/create-meeting', [TeleController::class, 'createMeeting']);
    Route::get('/get-prescription-details', [TeleController::class, 'getPrescription']);
    Route::post('/decline-tele/{id}', [TeleController::class, 'declineTele']);

    // Notification
    Route::get('/fetch-notification', [NotiFController::class, 'fetchNotif']);
    Route::get('/notif-patient-info/{id}', [NotiFController::class, 'patientInfo']);
    Route::post('/notif-patient-accept/{id}', [NotiFController::class, 'patientAccept']);
});
