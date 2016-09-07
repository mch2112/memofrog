//noinspection JSUnresolvedVariable
module.exports = function (grunt) {

    var jsIntroFiles =
        [
            "jssrc/jquery.js",
            "jssrc/Ajax.js",
            "jssrc/dexie.js",
            "jssrc/validation.js"];

    var jsOwnFiles =
        [
            "jssrc/strict.js", // always first
            "jssrc/Session.js",
            "jssrc/DomManager.js",
            "jssrc/TipManager.js",
            "jssrc/EventManager.js",
            "jssrc/GoogleAnalytics.js",
            "jssrc/Ajax.js",
            "jssrc/MemoQuery.js",
            "jssrc/DataQuery.js",
            "jssrc/Defer.js",
            "jssrc/frogDB.js",
            "jssrc/constants.js",
            "jssrc/memo.js",
            "jssrc/validation.js",
            "jssrc/Nav.js",
            "jssrc/navigation.js",
            "jssrc/browser_standard.js",
            "jssrc/render.js",
            "jssrc/tips.js",
            "jssrc/AlertBox.js",
            "jssrc/handshake.js",
            "jssrc/history.js",
            "jssrc/forms.js",
            "jssrc/filter.js",
            "jssrc/post.js",
            "jssrc/postMemo.js",
            "jssrc/postBucket.js",
            "jssrc/postStar.js",
            "jssrc/util.js",
            "jssrc/screens.js",
            "jssrc/placeholder.js",
            "jssrc/html.js",
            "jssrc/alarm.js",
            "jssrc/main.js"];

    var jsThirdPartyFiles = [
            "jssrc/jquery.js",
            "jssrc/dexie.js",
            "jssrc/moment.js",
            "jssrc/fastclick.js",
            "jssrc/hammer.js"];

    var jsOwnStdFiles = jsOwnFiles.concat(["jssrc/standard.js", "jssrc/notouch.js"]);
    var jsOwnTouchFiles = jsOwnFiles.concat(["jssrc/standard.js", "jssrc/touch.js"]);
    var jsOwnMobileTouchFiles = jsOwnFiles.concat(["jssrc/mobile.js", "jssrc/touch.js"]);

    var jsStdFiles = jsThirdPartyFiles.concat(jsOwnStdFiles);
    var jsTouchFiles = jsThirdPartyFiles.concat(jsOwnTouchFiles);
    var jsMobileTouchFiles = jsThirdPartyFiles.concat(jsOwnMobileTouchFiles);

    //noinspection JSUnresolvedFunction
    grunt.initConfig({
            pkg: grunt.file.readJSON('package.json'),
            compass: {
                all: {
                    options: {
                        sassDir: "sass",
                        cssDir: "tmp"
                    }
                }
            },
            concat: {
                intro: {
                    options: {separator: '\n'},
                    src: jsIntroFiles,
                    dest: "js/intro.js"
                },
                ownStd: {
                    options: {separator: '\n'},
                    src: jsOwnStdFiles,
                    dest: "tmp/jsOwnStdFiles.js"
                },
                ownTouch: {
                    options: {separator: '\n'},
                    src: jsOwnTouchFiles,
                    dest: "tmp/jsOwnTouchFiles.js"
                },
                ownMobileTouch: {
                    options: {separator: '\n'},
                    src: jsOwnMobileTouchFiles,
                    dest: "tmp/jsOwnMobileTouchFiles.js"
                },
                jsStd: {
                    options: {separator: '\n'},
                    src: jsStdFiles,
                    dest: "js/memofrog.js"
                },
                jsTouch: {
                    options: {separator: '\n'},
                    src: jsTouchFiles,
                    dest: "js/memofrogT.js"
                },
                jsMobileTouch: {
                    options: {separator: '\n'},
                    src: jsMobileTouchFiles,
                    dest: "js/memofrogMT.js"
                },
                cssIntro: {
                    options: {separator: '\n'},
                    src: ["tmp/intro.css"],
                    dest: 'css/intro.css'
                },
                cssStd: {
                    options: {separator: '\n'},
                    src: ["tmp/main.css", "tmp/standard.css", "tmp/notouch.css"],
                    dest: 'css/memofrog.css'
                },
                cssTouch: {
                    options: {separator: '\n'},
                    src: ["tmp/main.css", "tmp/standard.css", "tmp/touch.css"],
                    dest: 'css/memofrogT.css'
                },
                cssMobileTouch: {
                    options: {separator: '\n'},
                    src: ["tmp/main.css", "tmp/mobile.css", "tmp/touch.css"],
                    dest: 'css/memofrogMT.css'
                }
            },
            jshint: {
                options: {
                    browser: true,
                    strict: false,
                    globals: {
                        "moment": false,
                        "$": false,
                        "trackGA": false,
                        "FastClick": false,
                        "Hammer": false,
                        "Dexie": false,
                        "__appVersion" : false,
                        "__useGA": false,
                        "__defaultBucket": false,
                        "ga": false},
                    globalstrict: true,
                    devel: true
                },
                std: {
                    files: {
                        src: ['tmp/jsOwnStdFiles.js']
                    }
                },
                touch: {
                    files: {
                        src: ['tmp/jsOwnTouchFiles.js']
                    }
                },
                mobileTouch: {
                    files: {
                        src: ['tmp/jsOwnMobileTouchFiles.js']
                    }
                }
            },
            uglify: {
                options: {
                    banner: '/*! <%= pkg.name %> <%= grunt.template.today("dd-mm-yyyy") %> */\n',
                    mangle: true,
                    compress: {
                        drop_console: true
                        //pure_funcs: ["console.log"]
                    }
                },
                intro: {
                    files: {
                        "js/intro.min.js": ["js/intro.js"]
                    }
                },
                std: {
                    files: {
                        'js/memofrog.min.js': ['js/memofrog.js']
                    }
                },
                touch: {
                    files: {
                        'js/memofrogT.min.js': ['js/memofrogT.js']
                    }
                },
                mobileTouch: {
                    files: {
                        'js/memofrogMT.min.js': ['js/memofrogMT.js']
                    }
                }
            },
            cssmin: {
                intro: {
                    src: 'css/intro.css',
                    dest: 'css/intro.min.css'
                },
                std: {
                    src: 'css/memofrog.css',
                    dest: 'css/memofrog.min.css'
                },
                touch: {
                    src: 'css/memofrogT.css',
                    dest: 'css/memofrogT.min.css'
                },
                mobileTouch: {
                    src: 'css/memofrogMT.css',
                    dest: 'css/memofrogMT.min.css'
                }
            }
        }
    );

    //noinspection JSUnresolvedFunction
    grunt.loadNpmTasks('grunt-contrib-uglify');
    //noinspection JSUnresolvedFunction
    grunt.loadNpmTasks('grunt-contrib-watch');
    //noinspection JSUnresolvedFunction
    grunt.loadNpmTasks('grunt-contrib-concat');
    //noinspection JSUnresolvedFunction
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    //noinspection JSUnresolvedFunction
    grunt.loadNpmTasks('grunt-contrib-compass');
    //noinspection JSUnresolvedFunction
    grunt.loadNpmTasks('grunt-contrib-jshint');
    //noinspection JSUnresolvedFunction
    grunt.registerTask('default', ['compass', 'concat', 'uglify', 'cssmin']);
    //noinspection JSUnresolvedFunction
    grunt.registerTask('devjs', ['compass', 'concat', 'jshint']);
};