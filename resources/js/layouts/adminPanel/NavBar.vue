<template>
    <nav class="navbar navbar-expand-lg main-navbar sticky">
        <div class="form-inline mr-auto">
            <ul class="navbar-nav mr-3">
                <li><a href="#" data-toggle="sidebar" class="text-dark"> <i class="fas fa-bars"></i></a></li>
                <li><a href="#" class="nav-link nav-link-lg fullscreen-btn">
                        <i data-feather="maximize"></i>
                    </a></li>
                <li>
<!--                    <form class="form-inline mr-auto">-->
<!--                        <div class="search-element">-->
<!--                            <input class="form-control" type="search" placeholder="Search" aria-label="Search"-->
<!--                                data-width="200">-->
<!--                            <button class="btn" type="submit">-->
<!--                                <i class="fas fa-search"></i>-->
<!--                            </button>-->
<!--                        </div>-->
<!--                    </form>-->
                </li>
            </ul>
        </div>
        <ul class="navbar-nav navbar-right">


            <li class="dropdown"><a href="#" data-toggle="dropdown"
                    class="nav-link dropdown-toggle nav-link-lg nav-link-user"> <img alt="image"
                        :src="$store.state.main_url+'assets/img/user.png'" class="user-img-radious-style"> <span
                        class="d-sm-none d-lg-inline-block"></span></a>
                <div class="dropdown-menu dropdown-menu-right pullDown">
                    <div class="dropdown-title">Hello {{ $store.state.user.name }}</div>

                    <div class="dropdown-divider"></div>
                    <a href="#" @click="logout" class="dropdown-item has-icon text-danger"> <i
                            class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>
</template>
<script>
export default {
    data() {
        return {
            
        }
    },
    methods: {       
        async logout() {
           const res = await this.callApi("post", "logout", this.data);
            
            if (res.status == 200) {
                localStorage.removeItem("user");
                localStorage.removeItem("token");
                window.location.href = this.$store.state.main_url;
            } 
        }
    }
}
</script>