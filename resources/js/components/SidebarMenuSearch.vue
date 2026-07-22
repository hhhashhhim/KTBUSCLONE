<template>
    <div class="sidebar-search-wrap">
        <div class="form-group mb-0">
            <label :for="inputId" class="sidebar-search-label">Search Menu</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </div>
                </div>
                <input
                    :id="inputId"
                    v-model="query"
                    type="search"
                    class="form-control sidebar-search-input"
                    placeholder="Search menu"
                    autocomplete="off"
                    aria-label="Search sidebar menu"
                    @input="filterMenu"
                />
            </div>
            <small v-if="query.trim() && !hasResults" class="sidebar-search-empty">
                No menu found
            </small>
        </div>
    </div>
</template>

<script>
let nextSearchId = 0;

export default {
    name: "SidebarMenuSearch",
    data() {
        nextSearchId += 1;
        return {
            query: "",
            hasResults: true,
            inputId: `sidebar-menu-search-${nextSearchId}`,
            menu: null,
            searchActive: false,
        };
    },
    methods: {
        getDirectChild(element, selector) {
            return Array.from(element.children).find((child) => child.matches?.(selector)) || null;
        },
        setDropdownOpen(item, shouldOpen) {
            const submenu = this.getDirectChild(item, "ul.dropdown-menu");
            if (!submenu) return;

            if (shouldOpen) {
                if (!item.classList.contains("active")) {
                    item.dataset.searchOpened = "true";
                }
                item.classList.add("active", "opened");
                submenu.style.display = "block";
            } else {
                item.classList.remove("active", "opened");
                submenu.style.display = "none";
            }
        },
        filterItem(item, term) {
            if (!(item instanceof HTMLElement)) return false;

            const submenu = this.getDirectChild(item, "ul.dropdown-menu");
            const directLink = this.getDirectChild(item, "a");
            const ownText = (directLink?.textContent || "").toLowerCase();
            let childMatches = false;

            if (submenu) {
                Array.from(submenu.children).forEach((child) => {
                    const matches = this.filterItem(child, term);
                    child.style.display = matches ? "" : "none";
                    childMatches = childMatches || matches;
                });
            }

            const matches = ownText.includes(term) || childMatches;
            item.style.display = matches ? "" : "none";

            if (submenu) {
                this.setDropdownOpen(item, matches);
            }

            return matches;
        },
        resetMenu() {
            if (!this.menu) return;

            this.menu.querySelectorAll("li").forEach((item) => {
                item.style.display = "";
                if (item.dataset.searchWasActive !== undefined) {
                    item.classList.toggle("active", item.dataset.searchWasActive === "true");
                    item.classList.toggle("opened", item.dataset.searchWasOpened === "true");
                    delete item.dataset.searchWasActive;
                    delete item.dataset.searchWasOpened;
                    delete item.dataset.searchOpened;
                }
            });

            this.menu.querySelectorAll("ul.dropdown-menu").forEach((submenu) => {
                if (submenu.dataset.searchPreviousDisplay !== undefined) {
                    submenu.style.display = submenu.dataset.searchPreviousDisplay;
                    delete submenu.dataset.searchPreviousDisplay;
                }
            });
            this.hasResults = true;
            this.searchActive = false;
        },
        rememberMenuState() {
            this.menu.querySelectorAll("li.dropdown").forEach((item) => {
                item.dataset.searchWasActive = String(item.classList.contains("active"));
                item.dataset.searchWasOpened = String(item.classList.contains("opened"));
            });
            this.menu.querySelectorAll("ul.dropdown-menu").forEach((submenu) => {
                submenu.dataset.searchPreviousDisplay = submenu.style.display;
            });
            this.searchActive = true;
        },
        filterMenu() {
            const term = this.query.toLowerCase().trim();
            if (!this.menu || !term) {
                this.resetMenu();
                return;
            }

            if (!this.searchActive) {
                this.rememberMenuState();
            }

            let found = false;
            Array.from(this.menu.children).forEach((item) => {
                if (!(item instanceof HTMLElement)) return;

                if (item.classList.contains("menu-header")) {
                    item.style.display = "none";
                    return;
                }

                const matches = this.filterItem(item, term);
                item.style.display = matches ? "" : "none";
                found = found || matches;
            });
            this.hasResults = found;
        },
    },
    mounted() {
        this.menu = this.$el.closest("aside")?.querySelector(".sidebar-menu") || null;
    },
    beforeUnmount() {
        this.resetMenu();
    },
};
</script>

<style scoped>
.sidebar-search-wrap {
    padding: 12px 15px 8px;
    border-bottom: 1px solid #f1f3f5;
}

.sidebar-search-label {
    display: block;
    margin-bottom: 6px;
    color: #98a2b3;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.sidebar-search-wrap .input-group-text {
    background: #f8fafc;
    border-color: #d0d5dd;
    color: #667085;
}

.sidebar-search-input {
    border-color: #d0d5dd;
    box-shadow: none !important;
}

.sidebar-search-empty {
    display: block;
    padding-top: 7px;
    color: #dc3545;
}
</style>
