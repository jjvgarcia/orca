<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QuotationApprovalController;
use App\Livewire\Auth\Login;
use App\Livewire\Customers\Form as CustomerForm;
use App\Livewire\Customers\Index as CustomersIndex;
use App\Livewire\Dashboard;
use App\Livewire\Orders\Index as OrdersIndex;
use App\Livewire\Orders\Show as OrderShow;
use App\Livewire\Quotations\Builder as QuotationBuilder;
use App\Livewire\Quotations\Index as QuotationsIndex;
use App\Livewire\Quotations\Show as QuotationShow;
use App\Livewire\Services\Index as ServicesIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::get('/login', Login::class)->middleware('guest')->name('login');
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('signed')->group(function () {
    Route::get('/q/{quotation}', [QuotationApprovalController::class, 'show'])->name('quotation.public');
    Route::post('/q/{quotation}/decision', [QuotationApprovalController::class, 'decide'])->name('quotation.decision');
    Route::get('/q/{quotation}/pdf', [QuotationApprovalController::class, 'pdf'])->name('quotation.pdf');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/customers', CustomersIndex::class)->name('customers.index');
    Route::get('/customers/create', CustomerForm::class)->name('customers.create');
    Route::get('/customers/{customer}/edit', CustomerForm::class)->name('customers.edit');

    Route::get('/services', ServicesIndex::class)->name('services.index');

    Route::get('/quotations', QuotationsIndex::class)->name('quotations.index');
    Route::get('/quotations/create', QuotationBuilder::class)->name('quotations.create');
    Route::get('/quotations/{quotation}', QuotationShow::class)->name('quotations.show');
    Route::get('/quotations/{quotation}/revise', QuotationBuilder::class)->name('quotations.revise');

    Route::get('/orders', OrdersIndex::class)->name('orders.index');
    Route::get('/orders/{order}', OrderShow::class)->name('orders.show');

    Route::post('/payments', PaymentController::class)->name('payments.store');
});
