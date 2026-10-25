export class Affiche{
    div;
    h2;
    p;
    
    constructor(Titre, Texte, Photo){
        this.div = document.createElement("div");
        this.h2 = document.createElement("h2");
        this.p = document.createElement("p");

        this.div.classList.add("affiche");
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