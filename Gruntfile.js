module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		less: {
			themecss: {
				options: {
					paths: ["ess"],
					cleancss:true
				},
				files: {
					"style.css": "less/style.less"
				}
			}
		},
		jshint: {
			all: ['Gruntfile.js', 'js/admin.js', 'js/scripts.js']
		},
		uglify: {
			themejs: {
				options: {
					// the banner is inserted at the top of the output
					banner: '/*!\n * Leeds Talent Pool theme javascript\n * @author <%= pkg.author %>\n * @version <%= pkg.version %>\n * generated: <%= grunt.template.today("dd-mm-yyyy") %>\n */\n',
					mangle: false
				},
				files: {
					'js/scripts.min.js': ['js/scripts.js']
				}
			},
			adminjs: {
				options: {
					// the banner is inserted at the top of the output
					banner: '/*!\n * Leeds Talent Pool theme javascript for Wordpress Dashboard\n * @author <%= pkg.author %>\n * @version <%= pkg.version %>\n * generated: <%= grunt.template.today("dd-mm-yyyy") %>\n */\n',
					mangle: false
				},
				files: {
					'js/admin.min.js': ['js/admin.js']
				}
			}
		},
		watch: {
			less: {
				files: ['less/*.less'],
				tasks: ['css']
			},
			js: {
				files: ['js/scripts.js', 'js/admin.js'],
				tasks: ['js']
			}
		}
	});

	// Load the plugins that provide the tasks.
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-jshint');

	// Default task(s).
	grunt.registerTask('default', ['watch']);

	// js compilation task
	grunt.registerTask('js', ['jshint', 'uglify']);
	// less compilation task
	grunt.registerTask('css', ['less']);
	// build task
	grunt.registerTask('build', ['css', 'js']);
};