export class Affiche{
    div;
    h2;
    p;
    
    constructor(Titre, Texte, Photo){
        this.div = document.createElement("div");
        this.h2 = document.createElement("h2");
        this.p = document.createElement("p");

        this.div.classList.add("affiche");
        this.div.style.backgroundImage = 'url("./Start Screen.jpg")';
        this.h2.innerText = Titre;
        this.p.innerText = Texte;

        this.p.hidden = true

        this.div.appendChild(this.h2)
        this.div.appendChild(this.p)

        this.div.addEventListener("click", () =>{
            console.log(Texte);
            this.p.hidden = !this.p.hidden;
        });
    }

    get_div(){
        return this.div;
    }
}

/*docker / build git
	•	cahier des charges
	•	guide utilisateur
	•	plaquette promotionelle (visée utilisateur)
	•	option :
	◦	rapport "agile" du projet
*/