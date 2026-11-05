import {Affiche} from "./Affiche.js";


const div_selection_affiche = document.getElementById("Selection_Affiche");
const div_selection_menu = document.getElementById("Selection_Menu");
const div_affiche = document.getElementById("div_affiche");
const div_menu = document.getElementById("div_menu");
const colonne_gauche_div = document.getElementById("colonne_gauche");
const colonne_droite_div = document.getElementById("colonne_droite");
const derniere_colonne_div = document.getElementById("derniere_colonne");
let tableau_affiche = [];
let tableau_photo_affiche = [];


main();

function main(){
    construire_tableau();
    contruire_affiches();
    console.log(colonne_gauche_div);
    console.log(colonne_droite_div);
    console.log(derniere_colonne_div);
    console.log(div_affiche);
    console.log(div_menu);
    div_affiche.hidden = true
}

function construire_tableau(){
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