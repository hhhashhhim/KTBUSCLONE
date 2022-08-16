<template>
  <div>
    <h1>this is from main app</h1>
    <router-view></router-view>
    
        <router-link to="/">home</router-link> <br>
        <router-link to="/profile">profile</router-link> <br>
    
  </div>
</template>

<script>
// import Welcome from './Welcome.vue';
// import Profile from './Profile.vue';

export default {
    name:"mainapp",
    // components:{
    //     Welcome,
    //     Profile
    // }
}
</script>

<style>

</style>