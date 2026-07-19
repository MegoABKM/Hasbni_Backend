<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// 🚀 هذا هو المسار الذي كان مفقوداً وتسبب برفض الاتصال (403) 🚀
Broadcast::channel('shop.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});