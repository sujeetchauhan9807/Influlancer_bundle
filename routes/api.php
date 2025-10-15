<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthAdminController;
use App\Http\Controllers\Admin\CampaignApprovalController;
use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\CampaignProposalController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PlatformController;
use App\Http\Controllers\influencer\AuthInfluencerController;
use App\Http\Controllers\Influencer\InfluencerController;
use App\Http\Controllers\User\BrandController;
use App\Http\Controllers\User\AuthUserController;
use App\Http\Middleware\Admin\AdminMiddleware;
use App\Http\Controllers\User\CampaignController;
use App\Http\Controllers\Admin\CampaignTypeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Influencer\ProposalsController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\ReviewController;
use App\Http\Middleware\Influencer\InfluencerMiddleware;
use App\Http\Middleware\User\UserMiddleware;
use Illuminate\Support\Facades\Route;

/**************************************************************/
/******************* User Routes ******************************/
/**************************************************************/

// Guest routes (only not-logged-in users)

Route::post('/user/register', [AuthUserController::class, 'userRegister']);
Route::post('/user/login', [AuthUserController::class, 'login'])->name('user.login');
Route::post('/user/forgot-password', [AuthUserController::class, 'forgotPassword']);
Route::post('/user/reset-password', [AuthUserController::class, 'resetPassword']);
Route::get('/user/verify-email/{token}', [AuthUserController::class, 'verifyEmail']);


// Authenticated user routes
Route::middleware(['auth:api', UserMiddleware::class])->group(function () {
  Route::post('/user/logout', [AuthUserController::class, 'logout']);
  Route::post('/user/profile/update', [AuthUserController::class, 'updateProfile']);
  Route::get('/user/verify-token', [AuthUserController::class, 'verifyToken']);

  Route::get('/user/dashboard/stats', [UserDashboardController::class, 'stats']);

  Route::get('/user/brand', [BrandController::class, 'index']);
  Route::post('/user/brand/add', [BrandController::class, 'addBrand']);
  Route::post('/user/brand/update/{id}', [BrandController::class, 'updateBrand']);
  Route::delete('/user/brand/delete/{id}', [BrandController::class, 'deleteBrand']);

  Route::get('/user/campaigns', [CampaignController::class, 'index']);
  Route::get('/user/campaigns/view/{id}', [CampaignController::class, 'viewPage']);
  Route::post('/user/campaigns/add', [CampaignController::class, 'addCampaign']);
  Route::post('/user/campaigns/update/{id}', [CampaignController::class, 'updateCampaign']);
  Route::delete('/user/campaigns/delete/{id}', [CampaignController::class, 'deleteCampaign']);

  Route::get('/user/campaigns/categories/list', [CampaignController::class, 'categoriesList']);
  Route::get('/user/campaigns/type/list', [CampaignController::class, 'campaignsTypeList']);
  Route::get('/user/campaigns/brand/list', [CampaignController::class, 'brandList']);

  Route::post('/user/review/add', [ReviewController::class, 'store']);
  Route::post('/user/review/update/{id}', [ReviewController::class, 'update']);
  Route::delete('/user/review/delete/{id}', [ReviewController::class, 'destroy']);
});

/**************************************************************/
/******************* Admin Routes *****************************/
/**************************************************************/

// Guest routes (only not-logged-in admins)

Route::post('/admin/login', [AuthAdminController::class, 'login'])->name('admin.login');


// Authenticated admin routes
Route::middleware(['auth:admin', AdminMiddleware::class])->group(function () {
  Route::post('/admin/logout', [AuthAdminController::class, 'logout']);
  Route::get('/admin/verify-token', [AuthAdminController::class, 'verifyToken']);

  Route::get('/admin/dashboard/stats', [DashboardController::class, 'stats']);

  Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.index');
  Route::post('/admin/user/status/{id}', [AdminController::class, 'UserStatus']);
  Route::get('/admin/user/{id}/edit', [AdminController::class, 'edit']);
  Route::post('/admin/user/update/{id}', [AdminController::class, 'update']);
  Route::delete('/admin/user/delete/{id}', [AdminController::class, 'destroy']);

  Route::get('/admin/campaignType', [CampaignTypeController::class, 'index']);
  Route::post('/admin/campaignType/add', [CampaignTypeController::class, 'addCampaignType']);
  Route::post('/admin/campaignType/update/{id}', [CampaignTypeController::class, 'updateCampaignType']);
  Route::delete('/admin/campaignType/delete/{id}', [CampaignTypeController::class, 'deleteCampaignType']);

  Route::get('/admin/category', [CategoryController::class, 'index']);
  Route::post('/admin/category/add', [CategoryController::class, 'addCategory']);
  Route::post('/admin/category/update/{id}', [CategoryController::class, 'updateCategory']);
  Route::delete('/admin/category/delete/{id}', [CategoryController::class, 'deleteCategory']);

  Route::get('/admin/platform', [PlatformController::class, 'index']);
  Route::post('/admin/platform/add', [PlatformController::class, 'addPlatform']);
  Route::post('/admin/platform/update/{id}', [PlatformController::class, 'updatePlatform']);
  Route::delete('/admin/platform/delete/{id}', [PlatformController::class, 'deletePlatform']);

  Route::get('/admin/influencer/list', [AdminController::class, 'influencer']);

  Route::get('/admin/campaign', [AdminCampaignController::class, 'index']);
  Route::post('/admin/campaign/update/{id}', [AdminCampaignController::class, 'updateCampaign']);
  Route::get('/admin/campaign/view/{id}', [AdminCampaignController::class, 'viewPage']);
  Route::post('/admin/campaign/status/update/{id}', [CampaignApprovalController::class, 'changeStatus']);
  Route::post('/admin/campaign/proposal/status/update/{id}', [CampaignProposalController::class, 'changeStatus']);
});


/**************************************************************/
/******************* Influencer Routes *****************************/
/**************************************************************/

// Guest routes (only not-logged-in influencer)

Route::post('/influencer/login', [AuthInfluencerController::class, 'login'])->name('influencer.login');
Route::post('/influencer/register', [AuthInfluencerController::class, 'influencerRegister']);
Route::get('/influencer/verify-email/{token}', [AuthInfluencerController::class, 'verifyEmail']);
Route::post('/influencer/forgot-password', [AuthInfluencerController::class, 'forgotPassword']);
Route::post('/influencer/reset-password', [AuthInfluencerController::class, 'resetPassword']);

Route::middleware(['auth:influencer', InfluencerMiddleware::class])->group(function () {
  Route::post('/influencer/logout', [AuthInfluencerController::class, 'logout']);
  Route::get('/influencer/campaigns', [InfluencerController::class, 'index']);
  Route::get('/influencer/campaigns/view/{id}', [InfluencerController::class, 'campaignView']);
  Route::post('/influencer/campaigns/status/{id}', [InfluencerController::class, 'campaignStatus']);

  Route::get('/influencer/verify-token', [AuthInfluencerController::class, 'verifyToken']);
  Route::post('/influencer/update/{id}', [InfluencerController::class, 'updateProfile']);
  Route::get('/influencer/view/profile', [InfluencerController::class, 'viewProfile']);

  Route::post('/influencer/proposal/send/{id}', [ProposalsController::class, 'postProposals']);
});
