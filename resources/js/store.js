import { createStore } from 'vuex';

const STORAGE_KEYS = {
    token: "token",
    user: "user",
};

const STORAGE_SYNC_RETRIES = 4;
const STORAGE_SYNC_DELAY_MS = 25;

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const safeStorage = {
    getItem(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (error) {
            return null;
        }
    },
    setItem(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (error) {
            // Ignore storage write failures (Safari private mode, quota limits, etc.)
        }
    },
    removeItem(key) {
        try {
            window.localStorage.removeItem(key);
        } catch (error) {
            // Ignore storage removal failures.
        }
    },
};

const parseJson = (value, fallback = null) => {
    if (!value) {
        return fallback;
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        return fallback;
    }
};

const getPermissions = (user) => {
    return Array.isArray(user?.role?.permissions) ? user.role.permissions : [];
};

const getCompanyModules = (user) => {
    if (user?.is_super_admin == 1 || !Array.isArray(user?.company?.modules)) {
        return [];
    }

    return user.company.modules;
};

const getPersistedAuthState = () => {
    const token = safeStorage.getItem(STORAGE_KEYS.token) || "";
    const user = token ? parseJson(safeStorage.getItem(STORAGE_KEYS.user), null) : null;

    return {
        token,
        user,
        permissions: getPermissions(user),
        companyModules: getCompanyModules(user),
    };
};

const initialState = () => {
    const persistedState = getPersistedAuthState();

    return {
        deletingObj: {
            url: "",
            data: "",
            index: -1,
            isDeleted: false,
        },
        user: persistedState.user,
        token: persistedState.token,
        permissions: persistedState.permissions,
        companyModules: persistedState.companyModules,
        main_url: process.env.MIX_MAIN_URL,
        api_url: process.env.MIX_API_URL,
        app_url: process.env.MIX_MAIN_URL,
        appStateReady: false,
    };
};

const store = createStore({
    state: initialState,
    getters: {
        getDeletingObj(state) {
            return state.deletingObj;
        },
        user(state) {
            return state.user;
        },
    },
    mutations: {
        setDeleteObj(state, obj) {
            state.deletingObj = obj;
        },
        hydrateAuthState(state, authState) {
            state.user = authState.user;
            state.token = authState.token;
            state.permissions = authState.permissions;
            state.companyModules = authState.companyModules;
        },
        setAppStateReady(state, value) {
            state.appStateReady = value;
        },
        updateUser(state, user) {
            state.user = user;
            state.permissions = getPermissions(user);
            state.companyModules = getCompanyModules(user);

            if (user) {
                safeStorage.setItem(STORAGE_KEYS.user, JSON.stringify(user));
            } else {
                safeStorage.removeItem(STORAGE_KEYS.user);
            }
        },
        updateToken(state, token) {
            state.token = token || "";

            if (state.token) {
                safeStorage.setItem(STORAGE_KEYS.token, state.token);
            } else {
                safeStorage.removeItem(STORAGE_KEYS.token);
            }
        },
        clearAuthState(state) {
            state.user = null;
            state.token = "";
            state.permissions = [];
            state.companyModules = [];
        },
        updateAppUrl(state, obj) {
            state.app_url = obj;
        },
    },
    actions: {
        async initializeAppState({ state, commit }, options = {}) {
            const { force = false } = options;

            if (state.appStateReady && !force) {
                return {
                    token: state.token,
                    user: state.user,
                };
            }

            let persistedState = getPersistedAuthState();

            // Give newly opened tabs a brief chance to observe storage writes.
            for (
                let attempt = 0;
                attempt < STORAGE_SYNC_RETRIES &&
                (!persistedState.token || !persistedState.user);
                attempt++
            ) {
                await wait(STORAGE_SYNC_DELAY_MS);
                persistedState = getPersistedAuthState();
            }

            commit("hydrateAuthState", persistedState);
            commit("setAppStateReady", true);

            return persistedState;
        },
        clearAuthState({ commit }) {
            safeStorage.removeItem(STORAGE_KEYS.user);
            safeStorage.removeItem(STORAGE_KEYS.token);
            commit("clearAuthState");
            commit("setAppStateReady", true);
        },
    },
});

export default store;
