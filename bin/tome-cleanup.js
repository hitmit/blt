#!/usr/bin/env node

const { promisify } = require('util');
const fs = require('fs');
const readFile = promisify(fs.readFile);
const writeFile = promisify(fs.writeFile);
const removeFile = promisify(fs.unlink);
const access = promisify(fs.access);
const path = require('path');

/**
 * DESCRIPTION
 * This script traverses the manifest file, `.src/content/meta/index.json`, that
 * Tome uses to import content and removes any unused entities from the `index.json`
 * file along with the files associated with them if they exist.
 *
 * REQUIREMENTS
 * Node >=v12
 *
 * EXAMPLE USAGE
 * Using Nodejs: $ node ./bin/tome-cleanup.js
 *           or: $ ./bin/tome-cleanup.js
 *
 */
(async function () {

  const index = await readFile(path.join(process.cwd(),'./src/content/meta/index.json'));
  const json = JSON.parse(index);
  const entities = Object.keys(json);
  const entitiesToRemove = [];

  entities.forEach(async (item, index) => {
    let [ entityName, uuid ] = item.split('.');
    let filename = `${item}.json`;
    const mainItem = item;
    const filePath = path.join(process.cwd(), 'src/content/', filename);
    try {
      const fileExists = await pathExists(filePath);
    } catch(err){

      // Get all dependency entities from node entities and remove their files as well.
      if(entityName === 'node') {
        json[mainItem].forEach((dependency) => {
          const [ entityName, uuid ] = dependency.split('.');
          const entities = ['cohesion_layout', 'paragraphs', 'media', 'taxonomy_term'];
          if(entities.includes(entityName)) {
            entitiesToRemove.push(dependency);
          }
        });
      }

      // Gather all files that don't exist in the ./src/content directory
      entities.forEach(item => {
        if(item.includes(mainItem)){
          entitiesToRemove.push(mainItem)
        }
      });
    }

    if((index + 1) === entities.length) {
      // Remove entities from the json object
      entitiesToRemove.forEach(async function(entity){
        delete json[entity];
        try {
          await removeFile(path.join(process.cwd(), 'src/content',`${entity}.json`));
        } catch(err){
          // Fail silently
        }
      });

      // Write the update json to file.
      try {
        await writeFile(`./src/content/meta/index.json`, JSON.stringify(json, null, 4));
        console.log(`Cleaned up ${entitiesToRemove.length} old entities from ./src/content/meta/index.json and removed unused files.`);
      } catch(err){
        console.log(err);
      }
    }

  });

  /**
   * Helper function that checks if a path exists
   *
   * @param {string} path
   * @returns
   */
  function pathExists(path){
    return access(path, fs.constants.F_OK);
  }

})()