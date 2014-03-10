  module.exports = function(grunt) {
  
    grunt.initConfig({
      pkg: grunt.file.readJSON('package.json'),
 
      qunit: { // internal task or name of a plugin (like "qunit")
        all: ['tests/*.html']
      }
    });

    // load up your plugins
    grunt.loadNpmTasks('grunt-contrib-qunit');

    // register one or more task lists
    grunt.registerTask('default', ['qunit']);
    //grunt.registerTask('taskName', ['taskToRun', 'anotherTask']);
  };

