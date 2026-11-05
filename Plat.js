export class Plat{
    table_row;
    plat;
    
    constructor(table_row, nom_du_plat){
        this.table_row = table_row;
        this.plat = document.createElement("td");

        this.plat.innerText = nom_du_plat
    }

    get_table_row(){
        return this.table_row;
    }

    get_plat(){
        return this.plat;
    }
}