<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RefactorDatastructure extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // drop all previous tables
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('parent_appointments');
        Schema::dropIfExists('users_groups');
        Schema::dropIfExists('users');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('throttle');

        //CREATES SCHOOLS TABLE
        Schema::create('schools', function ($table) {
            $table->engine = 'InnoDB';
            // TODO: Add additional school fields? (URL, ...)
            $table->increments('id')->unsigned();
            $table->string('name', 255);
            $table->string('opening', 5);
            $table->string('city');
            $table->string('lang', 5)->default('nl');
            $table->timestamps();
            $table->softDeletes();
        });

        //CREATES USERS TABLE
        Schema::create('users', function ($table) {
            $table->engine = 'InnoDB';
            // TODO: Make permissions an intermediate column, or remove it alltogether and base permissions solely off groups.
            // TODO: Permissions are at the moment null for all, are they even needed here?
            $table->increments('id')->unsigned();
            $table->string('email');
            $table->string('password');
            $table->string('lang', 5)->nullable();
            $table->text('permissions')->nullable();
            $table->boolean('activated')->default(0);
            $table->string('activation_code')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('persist_code')->nullable();
            $table->string('reset_password_code')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();

            //Defines the school a user belongs to
            $table->integer('school_id')->unsigned()->nullable();
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.

            $table->unique('email');
            $table->index('activation_code');
            $table->index('reset_password_code');
        });

        //CREATES GROUPS TABLE
        Schema::create('roles', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->unique();
            $table->text('permissions')->nullable();
            $table->timestamps();

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';

        });

        Schema::create('calendars', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->text('description');

            $table->timestamps();

            //Defines the school a calendar belongs to
            $table->integer('school_id')->unsigned()->nullable();
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');

            //Defines the parent a calendar belongs to
            $table->integer('parent_id')->unsigned()->nullable();
            $table->foreign('parent_id')->references('id')->on('calendars')->onDelete('cascade');

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';
            //$table->unique('name');
        });

        //CREATE USER ROLES PIVOT TABLE
        Schema::create('users_roles', function ($table) {
            $table->engine = 'InnoDB';
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';
            $table->primary(['user_id', 'role_id']);
        });


        //CREATE USER CALENDARS PIVOT TABLE
        Schema::create('users_calendars', function ($table) {
            $table->engine = 'InnoDB';
            $table->integer('user_id')->unsigned();
            $table->integer('calendar_id')->unsigned();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('calendar_id')->references('id')->on('calendars')->onDelete('cascade');

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';
            $table->primary(['user_id', 'calendar_id']);
        });


        //CREATE THROTTLE TABLE
        Schema::create('throttle', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('attempts')->default(0);
            $table->boolean('suspended')->default(0);
            $table->boolean('banned')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('banned_at')->nullable();

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->index('user_id');
        });

        //CREATES APPOINTMENTS TABLE
        Schema::create('parent_appointments', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->boolean('allday');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->timestamps();

            //Defines the calendar an appointment belongs to
            $table->integer('calendar_id')->unsigned();
            $table->foreign('calendar_id')->references('id')->on('calendars')->onDelete('cascade');
        });

        //CREATES APPOINTMENTS TABLE
        Schema::create('appointments', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->boolean('allday');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            /*
            // Repeat_type = day=>'d', week=>'w', month=>'M', year=>'y'
            $table->string('repeat_type')->nullbale();
            // Repeat_freq = every x days, weeks,...
            $table->integer('repeat_freq')->nullable();
            // Nr_repeat = number of times this event will be repeated
            $table->integer('nr_repeat')->nullable();
            */
            $table->timestamps();

            //Defines the group an appointment belongs to
            $table->integer('calendar_id')->unsigned();
            $table->foreign('calendar_id')->references('id')->on('calendars')->onDelete('cascade');
            //Defines the parent event
            $table->integer('parent_id')->unsigned()->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_roles');
        Schema::dropIfExists('users_calendars');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('parent_appointments');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('calendars');
        Schema::dropIfExists('throttle');
        Schema::dropIfExists('users');
        Schema::dropIfExists('schools');
    }

}
