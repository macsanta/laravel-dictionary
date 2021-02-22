
Vue.config.devtools = true

window.DictionaryBus = new Vue();

Vue.component('translations-list', {
    template: `
        <div class="list-group" >
            <div>
                <div class="row">
                    <div class='form-group col-md-6' >
                        <button class='btn btn-default' @click="addingMode=true" v-show="!addingMode" :disabled="!group">Dodaj</button>
                        <button class='btn btn-default pull-right' @click="importFromViews()">Importuj z widoków</button>
                        <button class='btn btn-default pull-right' @click="exportCsv()">Export Csv</button>
                        <button class='btn btn-default pull-right' @click="importingMode=true">Import Csv</button>
                    </div>
                    <div class="form-group col-md-6">
                        <input type='text' class='form-control' v-model='searchKeyword' v-show="language"  placeholder="Szukaj"/>
                    </div>
                </div>
                <div v-show="addingMode">
                    <div class='form-group'>
                        <label>Etykieta</label>
                        <input type='text' class='form-control' v-model='newLabel'/>
                    </div>
                    <div class='form-group' v-for="(lang, index) in languages">
                        <label>Wartośc w [{{ lang }}]</label>
                        <textarea class='form-control' v-model='newValue[index]'></textarea>
                    </div>
                    <div class='form-group'>
                        <button class='btn btn-primary' @click="store(language, group)">Dodaj</button>
                        <button class='btn btn-default' @click="addingMode=false">Anuluj</button>
                    </div>
                </div>
                <div v-show="importingMode">
                    <div class='form-group'>
                        <label>Plik Csv</label>
                        <input type='file' name="file" id="csv_import"/>
                    </div>
                    <div class='form-group'>
                        <button class='btn btn-primary' @click="importCsv()">Importuj</button>
                        <button class='btn btn-default' @click="importingMode=false">Anuluj</button>
                    </div>
                </div>
            </div>
            <div v-for="(translation,label) in translations">
                <a href="javascript:void(0)"  class="list-group-item" @click="edit(label)" :class="{ 'active' : (editedLabel==label) }">
                    <h4 class="list-group-item-heading">{{ label }}</h4>
                    <p class="list-group-item-text">{{ translation }}</p>
                </a>
                <div v-show="(editedLabel==label)">
                    <div class='form-group'>
                        <textarea class='form-control' v-model='translations[label]'></textarea>
                    </div>
                    <div class='form-group'>
                        <button class='btn btn-danger' @click="remove(group, label)">Usun</button>
                        <button class='btn btn-primary' @click="update(language, group, label, translation)">Zapisz</button>
                    </div>
                </div>
            </div>
        </div>
    `,

    props: [
        'language',
        'group',
    ],

    data() {
        return {
            translations: [],
            editedLabel: '',
            newValue: [],
            newLabel: '',
            addingMode: false,
            searchKeyword: '',
            languages: [],
            importingMode: false,
        }
    },

    mounted(){
        this.loadTranslations(this.language, this.group);
    },

    watch: {
        language: function (newLanguage, oldLanguage) {
            this.loadTranslations(newLanguage, this.group, this.searchKeyword)
        },
        group: function (newGroup, oldGroup) {
            this.loadTranslations(this.language, newGroup, this.searchKeyword)
        },
        searchKeyword: function (keyword) {
            this.loadTranslations(this.language, this.group, this.searchKeyword)
        },
    },

    created() {
        DictionaryBus.$on("languagesLoaded", (languages) => {
            this.languages = languages;
        });
    },

    methods: {
        loadTranslations(language, group, keyword)
        {
            if((!language)||(!group))
            {
                return false;
            }

            var url = '/'+dictionaryRouteName+'/'+language+'/'+group
            if(keyword)
            {
                url = url + '/' + keyword;
            }

            axios.get(url)
                .then(response => this.translations = response.data)
                .catch(function (error) {
                    console.log(error);
                });


        },
        edit(label)
        {
            DictionaryBus.$emit('editedChanged', label);
            this.editedLabel = label
        },
        store(language, group)
        {
            var values = {};
            var langs = this.languages;

            langs.forEach(function(element, index){
                values[langs[index]] = '';
            });
            this.newValue.forEach(function(element, index){
                values[langs[index]] = element;
            });

            axios.post('/'+dictionaryRouteName, {
                    'group' : group,
                    'label' : this.newLabel,
                    'values' : values
            }).then(
                Vue.set( this.translations, this.newLabel, values[this.language] ),
                this.newLabel = '',
                this.newValue = [],
            )
            .catch(function (error) {
                console.log(error);
            });
        },
        update(language, group, label, translation)
        {
             axios.patch('/'+dictionaryRouteName+'/update', {
                    'language' : language,
                    'group' : group,
                    'label' : label,
                    'value' : translation
            })
            .then(this.editedLabel = '')
            .catch(function (error) {
                console.log(error);
            });
        },
        remove(group, label)
        {
            axios.delete('/'+dictionaryRouteName, {
                params: {
                    'group' : group,
                    'label' : label,
                }
            })
            .then(
                Vue.delete(this.translations, label),
                this.editedLabel = ''
            )
            .catch(function (error) {
                console.log(error);
            });
        },
        importFromViews()
        {
            axios.post('/'+dictionaryRouteName+'/importfromviews')
                .then(() => {
                        this.loadTranslations(this.language, this.group, this.searchKeyword)
                    }
                ).catch(function (error) {
                console.log(error);
            });
        },
        exportCsv()
        {
            window.location.href = '/'+dictionaryRouteName+'/export';
        },
        importCsv()
        {
            const fileInput = document.querySelector( '#csv_import' );
            const formData = new FormData();
            formData.append( 'file', fileInput.files[0] );
            axios.post( '/'+dictionaryRouteName+'/import', formData )
                .then( ( response ) => {
                    this.importingMode = false
                    this.loadTranslations(this.language, this.group, this.searchKeyword)
                } )
                .catch(function( error ) {
                } );
        }

    }
});

Vue.component('languages-list', {
    template: `
        <div>
            <div class="btn-group form-group" role="group" aria-label="Languages">
                <button class="btn btn-default" :class="{ 'btn-primary' : (activeLanguage==language) }" v-for="language in languages" @click="changeLanguage(language)" >{{ language }}</button>
            </div>
        </div>
    `,

    props: [
        'language',
    ],

    data() {
        return {
            languages: [],
            activeLanguage: this.language,
        }
    },

    mounted() {
        this.loadLanguages();
    },

    methods: {
        loadLanguages()
        {
            axios.get('/'+dictionaryRouteName+'/languages')
                .then((response) => {
                    this.languages = response.data;
                    if(this.languages)
                    {
                        this.changeLanguage(this.languages[0]);
                    }
                    DictionaryBus.$emit('languagesLoaded', this.languages);
                })
                .catch(function (error) {
                    console.log(error);
                });
        },
        changeLanguage(language)
        {
            DictionaryBus.$emit('languageChanged', language);
            this.activeLanguage = language
        }
    }

});

Vue.component('groups-list', {
    template: `
        <div>
            <div class="btn-group form-group" role="group" aria-label="Groups">
                <button class="btn btn-default" :class="{ 'btn-primary' : (activeGroup==group) }" v-for="group in groups" @click="changeGroup(group)" >{{ group }}</button>
            </div>
        </div>
    `,

    props: [
        'language',
        'group',
    ],

    data() {
        return {
            groups: [],
            activeGroup: this.group,
        }
    },

    mounted() {
        this.loadGroups(this.language);
    },

    watch: {
        language: function (newLanguage, oldLanguage) {
            this.loadGroups(newLanguage);
        }
    },

    methods: {
        loadGroups(language)
        {
            if(!language)
            {
                return;
            }

            axios.get('/'+dictionaryRouteName+'/groups/'+language)
                .then(response => this.groups = response.data)
                .catch(function (error) {
                    console.log(error);
                });
        },
        changeGroup(group)
        {
            DictionaryBus.$emit('groupChanged', group);
            this.activeGroup = group;
        }
    }

});



Vue.component('dictionary', {
    template: `
        <div>
            <div class='row'>
                <div class='col-md-6'>
                    <groups-list :group="activeGroup" :language="activeLanguage"></groups-list>
                </div>
                <div class='col-md-6 text-right'>
                    <languages-list :language="activeLanguage"></languages-list>
                </div>
            </div>
            
            <div class='row'>
                <div class='col-md-12'>
                    <translations-list :group="activeGroup" :language="activeLanguage"></translations-list>
                </div>
            </div>
        </div>
    `,

    data() {
        return {
            activeLanguage: '',    
            activeGroup: '',    
        }
    },

    created() {
        DictionaryBus.$on("languageChanged", (language) => {
            this.activeLanguage = language;
        });
        DictionaryBus.$on("groupChanged", (group) => {
            this.activeGroup = group;
        });
    },
});

var app = new Vue({
    el: '#dictionary'
});
