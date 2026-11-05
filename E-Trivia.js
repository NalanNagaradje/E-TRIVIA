import {Affiche} from "./Affiche.js";
import {Plat} from "./Plat.js";


const div_selection_affiche = document.getElementById("Selection_Affiche");
const div_selection_menu = document.getElementById("Selection_Menu");

div_selection_affiche.addEventListener("click", () =>{
    if (div_affiche.hidden == true){
        console.log("Va montrer les affiches")
        div_menu.hidden = true;
        div_affiche.hidden = false;
    }
});
div_selection_menu.addEventListener("click", () =>{
    if (div_menu.hidden == true){
        console.log("Va montrer le menu")
        div_affiche.hidden = true;
        div_menu.hidden = false;
    }
});

const div_affiche = document.getElementById("div_affiche");
const div_menu = document.getElementById("div_menu");
const colonne_gauche_div = document.getElementById("colonne_gauche");
const colonne_droite_div = document.getElementById("colonne_droite");
const derniere_colonne_div = document.getElementById("derniere_colonne");
const table_row_cuisine_du_monde = document.getElementById("Cuisine du Monde");
const table_row_friterie = document.getElementById("Friterie");
const table_row_pizzeria = document.getElementById("Pizzeria");
const table_row_entrees = document.getElementById("Entrées");
const table_row_desserts = document.getElementById("Desserts");
let tableau_affiche = [];
let tableau_photo_affiche = [];
let tableau_plat=[];


main();

function main(){
    remplir_affiches();
    contruire_affiches();
    div_affiche.hidden = true
    console.log(table_row_cuisine_du_monde)
    console.log(table_row_friterie)
    console.log(table_row_pizzeria)
    console.log(table_row_entrees)
    console.log(table_row_desserts)
    remplir_menu()
    construire_menu()
}

function remplir_affiches(){
    let nouvelle_affiche = new Affiche("Bonjour", "Je suis une affiche", "Lien vers la photo")
    tableau_affiche.push(nouvelle_affiche);
    let nouvelle_affiche2 = new Affiche("Salut", "Je suis la seconde affiche créé par javascript", "Lien vers la photo")
    tableau_affiche.push(nouvelle_affiche2);
    let nouvelle_affiche3 = new Affiche("Salute", "Je suis la troisième affiche créé par javascript", "Lien vers la photo")
    tableau_affiche.push(nouvelle_affiche3);
    let nouvelle_affiche4 = new Affiche("Saluter", "Je suis la quatrième affiche créé par javascript", "Lien vers la photo")
    tableau_affiche.push(nouvelle_affiche4);
    let nouvelle_affiche5 = new Affiche("Salutera", "Je suis la cinquième affiche créé par javascript", "Lien vers la photo")
    tableau_affiche.push(nouvelle_affiche5);
}

function contruire_affiches(){
    let limit;
    if (tableau_affiche.length % 2 == 0){
        limit = tableau_affiche.length;
    }
    else{
        limit = tableau_affiche.length-1;
        derniere_colonne_div.appendChild(tableau_affiche[limit].get_div()) /* On rajoute le dernier élement a la dernière colonne*/
        console.log("Ajout a la dernière colonne.");
    }
    for (let x=0; x < limit; x=x+1){
        if (x % 2 == 0){
            colonne_gauche_div.appendChild(tableau_affiche[x].get_div())
            console.log("Ajout a la colonne gauche");
        }
        else{
            colonne_droite_div.appendChild(tableau_affiche[x].get_div())
            console.log("Ajout a la colonne droite");
        }
    }
}

function remplir_menu(){
    let nouveau_plat=new Plat(table_row_cuisine_du_monde, "Poisson")
    tableau_plat.push(nouveau_plat);
}

function construire_menu(){
    for (let x=0; x < tableau_plat.length; x=x+1){
        console.log("Test")
        tableau_plat[x].get_table_row().appendChild(tableau_plat[x].get_plat())
    }
}