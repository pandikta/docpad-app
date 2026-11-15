<script setup>
import ServerError from '@/Pages/Errors/ServerError.vue';
</script>

<template>
    <ServerError :status="429" />
</template>
