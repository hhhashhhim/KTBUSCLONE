<?php

use App\Http\Controllers\Inventory\BidSummariesController;
use App\Http\Controllers\Inventory\MaterialRequestController;
use App\Http\Controllers\Inventory\ProductCategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\ProductUnitController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\Inventory\PurchaseRequisitionNoteController;
use App\Http\Controllers\Inventory\ReportsController;
use App\Http\Controllers\Inventory\StockInwardController;
use App\Http\Controllers\Inventory\StockOutwardController;
use App\Http\Controllers\Inventory\SupplierController;
use Illuminate\Support\Facades\Route;

// Reports
Route::middleware(['auth:sanctum'])->prefix('web/v1/reports')->group(function () {
    Route::post('/received', [ReportsController::class, 'received']);     
    Route::post('/issued', [ReportsController::class, 'issued']);   
    Route::post('/product-control-register', [ReportsController::class, 'product_control']);   
    // filter
    Route::post('/filter/product-control-register', [ReportsController::class, 'filter_product_control']);   
    Route::post('/filter/filter_received', [ReportsController::class, 'filter_received']);   
    Route::post('/filter/filter_issued', [ReportsController::class, 'filter_issued']);   
});
//PDFs
Route::middleware(['custom.sanctum.token.verify'])->prefix('web/v1/')->group(function () {
    Route::post('mr/pdf/', [MaterialRequestController::class, 'mrPDF']);
    Route::post('product/pdf/', [MaterialRequestController::class, 'productPDF']);
    Route::post('prn/pdf/', [PurchaseRequisitionNoteController::class, 'prnPDF']);
    Route::post('bid/pdf/', [BidSummariesController::class, 'bidPDF']);
    Route::post('po/pdf/', [PurchaseOrderController::class, 'poPDF']);
    Route::post('grn/pdf', [StockInwardController::class, 'grnPDF']);
    Route::post('sin/pdf', [StockOutwardController::class, 'sinPDF']);
});
// Stock Inward
Route::middleware(['auth:sanctum'])->prefix('web/v1/outward')->group(function () {
    Route::post('/', [StockOutwardController::class, 'index']);     
    Route::post('store', [StockOutwardController::class, 'store']);

});
// Stock Inward
Route::middleware(['auth:sanctum'])->prefix('web/v1/inward')->group(function () {
    Route::post('/', [StockInwardController::class, 'index']);    
    Route::post('store', [StockInwardController::class, 'store']);
    Route::post('/get-inward-details', [StockInwardController::class, 'getInwardDetails']);
});
// POs
Route::middleware(['auth:sanctum'])->prefix('web/v1/pos')->group(function () {
    Route::post('/', [PurchaseOrderController::class, 'index']);          
    Route::post('store', [PurchaseOrderController::class, 'store']);  
    Route::post('show', [PurchaseOrderController::class, 'show']);  
    Route::post('getSingle', [PurchaseOrderController::class, 'getSingle']);  
});
// Bid Summaries
Route::middleware(['auth:sanctum'])->prefix('web/v1/bid-summaries')->group(function () {
    Route::post('/', [BidSummariesController::class, 'index']);          
    Route::post('store', [BidSummariesController::class, 'store']);       
    Route::post('/show', [BidSummariesController::class, 'show']);
    Route::post('/compareBids', [BidSummariesController::class, 'compareBid']);
    Route::post('update', [BidSummariesController::class, 'update']);   
    Route::post('delete', [BidSummariesController::class, 'delete']);   
});
// PRN
Route::middleware(['auth:sanctum'])->prefix('web/v1/prn')->group(function () {
    Route::post('/', [PurchaseRequisitionNoteController::class, 'index']);        
    Route::post('store', [PurchaseRequisitionNoteController::class, 'store']);      
    Route::post('viewPRN', [PurchaseRequisitionNoteController::class, 'view']);  
    Route::post('fetch-prn-products', [PurchaseRequisitionNoteController::class, 'fetchPrnProducts']);
});
// MR
Route::middleware(['auth:sanctum'])->prefix('web/v1/mr')->group(function () {
    Route::post('/', [MaterialRequestController::class, 'index']);        
    Route::post('store', [MaterialRequestController::class, 'store']);      
    Route::post('view', [MaterialRequestController::class, 'view']); 
    Route::post('detail-update', [MaterialRequestController::class, 'update']);
    Route::post('mr-delete', [MaterialRequestController::class, 'mr_destroy']);
    Route::post('detail-delete', [MaterialRequestController::class, 'destroy']); 
    Route::post('buses', [MaterialRequestController::class, 'bus']); 
    Route::get('products/{id}/issuance-history', [ProductController::class, 'issuanceHistory']);

});
// Products
Route::middleware(['auth:sanctum'])->prefix('web/v1/inventory-product')->group(function () {
    Route::post('/', [ProductController::class, 'index']);        
    Route::post('store', [ProductController::class, 'store']);    
    Route::post('update', [ProductController::class, 'update']);  
    Route::post('/delete', [ProductController::class, 'delete']);  
});
// Product Category
Route::middleware(['auth:sanctum'])->prefix('web/v1/inventory-product-category')->group(function () {
    Route::post('/', [ProductCategoryController::class, 'index']);        
    Route::post('store', [ProductCategoryController::class, 'store']);    
    Route::post('update', [ProductCategoryController::class, 'update']);  
    Route::post('/delete', [ProductCategoryController::class, 'delete']);  
});
// Product Unit
Route::middleware(['auth:sanctum'])->prefix('web/v1/inventory-product-unit')->group(function () {
    Route::post('/', [ProductUnitController::class, 'index']);        
    Route::post('store', [ProductUnitController::class, 'store']);    
    Route::post('update', [ProductUnitController::class, 'update']);  
    Route::post('/delete', [ProductUnitController::class, 'delete']);  
});
// Suppliers
Route::middleware(['auth:sanctum'])->prefix('web/v1/supplier')->group(function () {
    Route::post('/', [SupplierController::class, 'index']);        
    Route::post('store', [SupplierController::class, 'store']);    
    Route::post('update', [SupplierController::class, 'update']);  
    Route::post('/delete', [SupplierController::class, 'delete']);  
});
// navbar requests count
Route::middleware(['auth:sanctum'])->prefix('web/v1/allRequests')->group(function () {
    Route::post('/', [ProductController::class, 'allRequests']);
});
