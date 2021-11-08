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
 * Tome uses to import content and logs any pages that share the same cohesion_layout
 * entity UUID. Pages that share the same cohesion_layout entity UUID means that the entity clone
 * module may need to be patched. Otherwise on the next Tome import, changes will be lost
 * to one of the pages.
 * https://cohesiondocs.acquia.com/6.6/knowledge-base/can-i-use-entity-clone-module-acquia-cohesion
 *
 * REQUIREMENTS
 * Node >=v12
 *
 * EXAMPLE USAGE
 * Using Nodejs: $ node ./bin/tome-check-entity-clone-issues.js
 *           or: $ ./bin/tome-check-entity-clone-issues.js
 *
 */
(async function () {

  const index = await readFile(path.join(process.cwd(),'./src/content/meta/index.json'));
  const json = JSON.parse(index);
  const entities = Object.keys(json);
  const nodes = entities.filter(entity=>entity.includes('node.'));
  const cohesionEntities = {};

  nodes.forEach(node=>{
    const re = /cohesion_layout/;
    if(json[node].length){
      const cohesionEntity = json[node].filter(item=>re.test(item));
      if(cohesionEntity.length){
        cohesionEntities[cohesionEntity[0]] = cohesionEntities[cohesionEntity[0]] || [];
        cohesionEntities[cohesionEntity[0]].push(node);
      }
    }
  });


  let foundEntitiesWithSharedCohesionLayoutUUID = false;
  let entitiesFound = 0;
  Object.keys(cohesionEntities).forEach(async (entityName, index) => {

    if(Object.keys(cohesionEntities).length === (index + 1) && foundEntitiesWithSharedCohesionLayoutUUID){
      console.log(`Found ${entitiesFound} page(s) that may need to be re-cloned due to shared cohesion_layout entities`);
    }

    if(Object.keys(cohesionEntities).length === (index + 1) && !foundEntitiesWithSharedCohesionLayoutUUID){
      console.log('Your site is all good!');
    }

    if(cohesionEntities[entityName].length > 1) {
      entitiesFound++;
      foundEntitiesWithSharedCohesionLayoutUUID = true;
      cohesionEntities[entityName].forEach(async (nodeName) => {
        const filePath = path.join(process.cwd(), 'src/content/', `${nodeName}.json`);
        const file = await readFile(filePath, 'utf8');
        const json = JSON.parse(file);
        const title = json.title[0].value;
        const alias = json.path[0].alias;
        console.log(`Title: ${title}\nURL:  ${alias}\n`)
      });
    }
  });

})()