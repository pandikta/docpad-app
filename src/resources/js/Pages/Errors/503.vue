<script setup>
import ServerError from '@/Pages/Errors/ServerError.vue';
</script>

<template>
    <ServerError :status="503" />
</template>
