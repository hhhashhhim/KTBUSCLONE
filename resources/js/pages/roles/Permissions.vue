<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Permissions</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between">
                                            <h4>{{ role.name }} of {{
                                                    role.company ? role.company.name : "Not Found"
                                                }}</h4>
                                            <button class="btn btn-primary" @click="save" :disabled="loading || saving || !canUpdatePermissions">{{ saving ? 'Saving…' : 'SAVE' }}</button>
                                        </div>
                                        <div class="card-body">
                                            <p v-if="loading" role="status">Loading role permissions…</p>
                                            <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
                                            <button v-if="!loading && !role" class="btn btn-outline-primary mb-3" @click="load">Retry loading permissions</button>
                                            <p v-if="role && !canUpdatePermissions" class="alert alert-info">Read-only: your role needs Edit role permission to save changes.</p>
                                            <div class="alert alert-success alert-dismissible fade show" role="alert"
                                                 v-if="success">
                                                <button type="button" class="close" data-dismiss="alert"
                                                        aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                    <span class="sr-only">Close</span>
                                                </button>
                                                {{ success }}
                                            </div>

                                            <fieldset class="role-permission-editor" :disabled="loading || saving || !canUpdatePermissions">
                                            <div v-for="(moduleName, i) in permissions" :key="i">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="alert alert-info" style="background-color: #d9edf7 !important">
                                                            <div class="row">
                                                                <div class="col-md-2">
                                                                    <input type="checkbox" :value="true"
                                                                           v-model="moduleName.allow"
                                                                           :disabled="isAdminRole && ['call-center','mobile-app'].includes(moduleName.name)"
                                                                           :id="moduleName.name"/>
                                                                    <label class="text-capitalize text-bold ml-1"
                                                                           :for="moduleName.name" style="color: black">
                                                                        {{
                                                                            displayPermissionName(moduleName.name)
                                                                        }}</label>
                                                                </div>
                                                                <div class="col-md-10" v-if="moduleName.allow || moduleName.name === 'mobile-app'">
                                                                                <div v-if="moduleName.name === 'call-center'" class="cc-role-permissions">
                                                                                    <p v-if="isAdminRole" class="cc-role-help">Admin always has all Call Center permissions. These rights cannot be disabled.</p>
                                                                                    <p v-else class="cc-role-help">Choose the actions this role can use. Access scope controls whose records they can work with; it does not grant actions.</p>
                                                                                    <fieldset v-for="group in callCenterGroups(moduleName.buttons)" :key="group.name">
                                                                                        <legend>{{ group.name }}</legend>
                                                                                        <label v-for="item in group.items" :key="item.button.name" class="cc-role-option" :for="item.button.name">
                                                                                            <input type="checkbox" v-model="item.button.allow" :id="item.button.name" :disabled="isAdminRole">
                                                                                            <span><strong>{{ item.label }}</strong><small>{{ item.description }}</small></span>
                                                                                        </label>
                                                                                    </fieldset>
                                                                                </div>

                                                                    <div v-else-if="moduleName.name === 'mobile-app'" class="cc-role-permissions ma-role-permissions">
                                                                        <p v-if="isAdminRole" class="cc-role-help">Admin has all Mobile App privileges. App settings, app controls, and images can be managed regardless of booking route assignments. Admin can also manage accounts for passengers visible in their assigned scope. Passenger lists and booking records still follow assigned routes. These rights are managed by the server.</p>
                                                                        <p v-else class="cc-role-help">Enable the submodules this role can open, then select its permitted actions. A submodule alone does not grant account changes, seat removal, refunds, settings changes, or exports.</p>
                                                                        <p v-if="!moduleName.allow" class="ma-role-warning" role="status">Mobile App is disabled for this role. Selected privileges stay inactive until the module is enabled.</p>
                                                                        <p v-if="!mobileAppCompanyPermissions?.allow" class="ma-role-warning" role="status">Mobile App is disabled for this company. Role selections cannot override company access.</p>
                                                                        <p class="cc-role-help">Company access and each staff member's assigned routes still apply. Requirements are shown below; selecting an action does not automatically grant its prerequisites.</p>
                                                                        <fieldset><legend>Submodule access</legend><div class="ma-role-grid"><label v-for="submenu in moduleName.childs || []" :key="submenu.name" class="cc-role-option" :for="`mobile-submodule-${submenu.name}`"><input type="checkbox" v-model="submenu.allow" :id="`mobile-submodule-${submenu.name}`" :disabled="isAdminRole"><span><strong>{{ displayPermissionName(submenu.name) }}</strong><small>{{ mobileSubmoduleDescription(submenu.name) }}</small></span></label></div></fieldset>
                                                                        <fieldset v-for="group in mobileAppGroups(moduleName.buttons)" :key="group.name">
                                                                            <legend>{{ group.name }}</legend>
                                                                            <div class="ma-role-grid">
                                                                                <label v-for="item in group.items" :key="item.button.name" class="cc-role-option" :for="`mobile-privilege-${item.button.name}`">
                                                                                    <input type="checkbox" v-model="item.button.allow" :id="`mobile-privilege-${item.button.name}`" :disabled="isAdminRole" :aria-describedby="`mobile-help-${item.button.name}`">
                                                                                    <span :id="`mobile-help-${item.button.name}`">
                                                                                        <strong>{{ item.label }}</strong><small>{{ item.description }}</small>
                                                                                        <small v-if="item.requires.length">Requires: {{ item.requires.map(mobilePrivilegeLabel).join(', ') }}.</small>
                                                                                        <small v-for="warning in mobilePrivilegeWarnings(moduleName, item)" :key="warning" class="ma-role-warning">{{ warning }}</small>
                                                                                    </span>
                                                                                </label>
                                                                            </div>
                                                                        </fieldset>
                                                                    </div>

                                                                    <template v-else>
                                                                    <div class="alert alert-success" style="background-color: #dff0d8 !important"
                                                                         v-for="(menus, j) in moduleName.childs"
                                                                         :key="j">
                                                                        <div class="row">
                                                                            <div class="col-md-2">
                                                                                <input type="checkbox"
                                                                                       :value="true"
                                                                                       v-model="menus.allow"
                                                                                       :id="menus.name"/>
                                                                                <label
                                                                                    class="text-capitalize text-bold ml-1"
                                                                                    :for="menus.name"
                                                                                    style="color: black">{{
                                                                                        displayPermissionName(menus.name)
                                                                                    }}</label>
                                                                            </div>
                                                                            <div class="col-md-10" v-if="menus.allow">
                                                                                <div class="alert alert-danger"
                                                                                     style="background-color: #f2dede !important">
                                                                                    <div v-if="menus.buttons">
                                                                <span v-for="(button, k) in menus.buttons" :key="j">
                                                                    <input type="checkbox"
                                                                           :value="true"
                                                                           v-model="button.allow" :id="button.name"/>
                                                                    <label class="text-capitalize text-bold mx-2"
                                                                           :for="button.name"
                                                                           style="color: black"> {{
                                                                            button.name
                                                                        }}</label>
                                                                </span>
                                                                                    </div>
                                                                                    <div v-else><span
                                                                                        style="color: black">NO PAGE ACTION</span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- <table
                          class="table table-striped table-hover"
                          id="edit_loc"
                        >
                          <thead>
                            <tr>
                              <th style="width: 7% !important;">Sr No.</th>
                              <th style="width: 15% !important;">Module Name</th>
                              <th>Permissions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <template v-for="(moduleName,i) in permissions" :key="i">
                            <tr>
                              <td>{{ i+1 }}</td>
                              <td>
                                <div class="text-capitalize">
                                  {{ moduleName.name }}
                                </div>
                              </td>
                              <td>
                                <label class="colorinput mx-3">
                                  <span v-if="i!='name'">
                                        <input
                                          :checked="mod"
                                          type="checkbox"
                                          :value="true"
                                          class="colorinput-input"
                                          v-model="moduleName.allow"
                                    />
                                    <span class="colorinput-color bg-primary"></span>
                                  </span>
                                </label>
                              </td>
                            </tr>
                            <tr v-if="moduleName.allow">
                              <td colspan="2"></td>
                                <td class="py-5">
                                <label class="colorinput m-3" v-for="(menus,j) in moduleName.childs" :key="j">
                                  <span v-if="i!='name'">
                                        <input
                                          :checked="menus"
                                          type="checkbox"
                                          :value="true"
                                          class="colorinput-input"
                                          v-model="menus.allow"
                                    />
                                    <span class="colorinput-color bg-primary"></span>
                                    <span style="position:relative;left:5px;top:-10px;" class="text-capitalize"> {{ menus.name }} </span>
                                  </span>
                                </label>
                                </td>
                            </tr>
                            </template>
                          </tbody>
                        </table> -->
                                            </div>
                                            </fieldset>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>

export default {
    name: "Role",
    computed: {
        isAdminRole() { return String(this.role?.name || '').trim().toLowerCase() === 'admin'; },
    },
    data() {
        return {
            role: "",
            permissions: [],
            callCenterPrivileges: [],
            mobileAppPrivileges: [],
            mobileAppCompanyPermissions: null,
            canUpdatePermissions: false,
            loading: true,
            saving: false,
            error: '',
            success: false,
        };
    },
    async created() {
        $('.modal').remove();
        const currentRouteName = this.$route.name;
        if (currentRouteName == 'booking-page') {
            window.addEventListener('keydown', this.enterKey);
            window.addEventListener('keydown', this.altM);
        } else {
            window.removeEventListener('keydown', this.enterKey);
            window.removeEventListener('keydown', this.altM);
        }

        await this.load();
    },

    methods: {
        async load() {
            if (this.saving) return;
            this.loading = true;
            this.error = '';
            this.canUpdatePermissions = false;
            try {
                const res = await this.callApi('post', 'role/get', { id: this.$route.params.id });
                if (res?.status !== 200) throw new Error(this.responseError(res, 'Unable to load role permissions.'));
                this.role = res.data.role;
                this.permissions = res.data.permissions;
                this.callCenterPrivileges = res.data.call_center_privileges || [];
                this.mobileAppPrivileges = res.data.mobile_app_privileges || [];
                this.mobileAppCompanyPermissions = res.data.mobile_app_company_permissions || null;
                this.canUpdatePermissions = res.data.can_update_permissions === true;
            } catch (error) {
                this.role = '';
                this.permissions = [];
                this.error = error.message || 'Unable to load role permissions.';
            } finally { this.loading = false; }
        },
        responseError(res, fallback) {
            const errors = res?.data?.errors || res?.data?.Error;
            if (errors) return Object.values(errors).flat().join(' ');
            if (res?.status === 403) return 'You do not have permission to save this role.';
            return res?.data?.message || fallback;
        },
        displayPermissionName(name) {
            const mobileNames = {'mobile-app':'Mobile App','mobile-app-overview':'Overview','mobile-app-users':'App Users','mobile-app-bookings':'Bookings & Seats','mobile-app-payments':'Payments & Exceptions','mobile-app-content':'Fleet & Content','mobile-app-reports':'Reports','mobile-app-settings':'App Settings','mobile-app-notifications':'Notifications','mobile-app-activity':'Activity & Audit'};
            return mobileNames[name] || (name === 'sales' ? 'Advance Sale' : name === 'call-center' ? 'Call Center' : name);
        },
        mobileSubmoduleDescription(name) {
            return {'mobile-app-overview':'Monitor mobile accounts, login trends, bookings, sales, and payment exceptions.','mobile-app-users':'Open passenger profiles, account status, booking history, and mobile sessions.','mobile-app-bookings':'Inspect mobile bookings, ticket seats, and Routes & Schedules availability. Changes need separate privileges.','mobile-app-payments':'Inspect gateway transactions, booking links, and exceptions requiring review.','mobile-app-content':'Review app images, their home/gallery placements, visibility, ordering, and bus class.','mobile-app-reports':'Open sales, route, passenger, cancellation, and payment reports.','mobile-app-settings':'Read version requirements, maintenance, support contacts, and feature availability.','mobile-app-notifications':'Preview passenger announcements and review inbox/read/push acceptance counts. Sending and companywide broadcasts require separate privileges.','mobile-app-activity':'Inspect staff activity, reasons, and recorded changes.'}[name] || 'Allow access to this Mobile App submodule.';
        },
        mobileAppGroups(buttons = []) {
            const groups = {};
            for (const button of buttons || []) {
                const definition = this.mobileAppPrivileges.find(item => item.name === button.name);
                const group = definition?.group || 'Mobile App privileges';
                (groups[group] ||= []).push({button,label:definition?.label || button.name.replace(/[-_]/g,' '),description:definition?.description || '',submenu:definition?.submenu,requires:definition?.requires || []});
            }
            return Object.entries(groups).map(([name,items])=>({name,items}));
        },
        mobilePrivilegeLabel(name) {
            return this.mobileAppPrivileges.find(item => item.name === name)?.label || this.displayPermissionName(name);
        },
        mobileCompanyAllows(name) {
            const module = this.mobileAppCompanyPermissions;
            if (!module?.allow) return false;
            const definition = this.mobileAppPrivileges.find(item => item.name === name);
            return !!definition && (!definition.submenu || module.childs?.some(child => child.name === definition.submenu && child.allow))
                && !!module.buttons?.some(button => button.name === name && button.allow);
        },
        mobilePrivilegeWarnings(module, item) {
            if (!item.button.allow) return [];
            const warnings = [];
            if (!module.allow) warnings.push('Inactive: Mobile App is disabled for this role.');
            if (item.submenu && !module.childs?.some(child => child.name === item.submenu && child.allow)) {
                warnings.push(`Inactive: enable the ${this.displayPermissionName(item.submenu)} submodule.`);
            }
            const missing = item.requires.filter(name => !module.buttons?.some(button => button.name === name && button.allow));
            if (missing.length) warnings.push(`Inactive: also select ${missing.map(this.mobilePrivilegeLabel).join(', ')}.`);
            if (![item.button.name, ...item.requires].every(this.mobileCompanyAllows)) {
                warnings.push('Inactive: company access does not allow this action or one of its requirements.');
            }
            return warnings;
        },
        callCenterGroups(buttons = []) {
            const groups = {};
            for (const button of buttons) {
                const definition = this.callCenterPrivileges.find(item => item.name === button.name);
                const group = definition?.group || 'Other privileges';
                (groups[group] ||= []).push({ button, label: definition?.label || button.name, description: definition?.description || '' });
            }
            return Object.entries(groups).map(([name, items]) => ({ name, items }));
        },
        async save() {
            if (this.loading || this.saving || !this.canUpdatePermissions || !this.role) return;
            this.saving = true;
            this.error = '';
            this.success = '';
            try {
                const res = await this.callApi('post', 'role/update', { ...this.role, permissions: this.permissions });
                if (res?.status !== 201) throw new Error(this.responseError(res, 'Unable to save role permissions. Please try again.'));
                this.success = 'Role and permissions updated successfully.';
            } catch (error) {
                this.error = error.message || 'Unable to save role permissions. Please try again.';
            } finally { this.saving = false; }
        }
    },

};
</script>

<style scoped>
.role-permission-editor { min-width: 0; border: 0; padding: 0; margin: 0; }
.cc-role-permissions { padding: 18px; background: #dff0d8; border: 1px solid #cbded6; border-radius: 8px; color: #243c34; }
.cc-role-help { margin: 0 0 16px; color: #526b61; }
.cc-role-permissions fieldset { border: 0; border-top: 1px solid #dce7e1; padding: 16px 0 4px; margin: 0; }
.cc-role-permissions legend { float: none; width: auto; padding-right: 12px; font-size: 15px; font-weight: 700; }
.cc-role-option { display: flex; align-items: flex-start; gap: 10px; padding: 6px 0; cursor: pointer; }
.cc-role-option input { flex: none; margin-top: 4px; width: 17px; height: 17px; accent-color: #196953; }
.cc-role-option strong, .cc-role-option small { display: block; }
.cc-role-option small { margin-top: 2px; color: #526b61; line-height: 1.45; font-size: 13px; }
.ma-role-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px 24px; }
.ma-role-permissions .cc-role-option input { margin-top:4px; }
.ma-role-permissions .ma-role-warning { color: #8a3a09; font-weight: 600; }
@media(max-width:900px) { .ma-role-grid { grid-template-columns:1fr; } }
</style>
