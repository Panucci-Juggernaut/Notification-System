<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_new_ip')->default(false);
            $table->timestamp('logged_in_at');
            $table->timestamps();

            $table->index(['user_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_login_histories');
    }
};
