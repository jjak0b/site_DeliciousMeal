<?php
    include_once('../../config.php' );
    include_once('../../scripts/utility.php' );
    include_once('../../scripts/shared_site.php' );
    
    if( !isset( $connection ) ){
        $connection = mysqli_connect(HOST, USER, PASSWORD, DB_NAME );
    }

    // Check connection
    if ( mysqli_connect_errno() )
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    $value;
    if( isset( $_POST['value'] ) ){
        $value = json_decode($_POST['value'], true);
    }
?>
<details style="text-align: left; display: block; width: 100%;">
    <summary>Ordini domicilio</summary>
    <?php
        $unita = get_unita( $connection,
                    $_SESSION['user_login']['id'] );
        $tavolo;
        $id_utente;
        if( !$unita ){ // filtra la query in base all'utente non dipendente
            $id_utente = $_SESSION['user_login']['id'];
        }
        $query_ordini_domicilio = "SELECT od.id as id_ordine,
                                    o.id_utente,
                                    od.giorno_consegna,
                                    od.comune,
                                    od.citta,
                                    od.indirizzo
                                FROM ordini_domicilio od, ordini o
                                WHERE od.id = o.id";
        if( isset( $id_utente ) ){
            $query_ordini_domicilio .= " AND o.id_utente = \"$id_utente\"";
        }
        if( isset( $value ) && isset( $value['date'] ) ){
            $query_ordini_domicilio .= " AND cast(od.giorno_consegna as date) = \"".$value['date']."\"";
        }
        $query_ordini_domicilio .= " ORDER BY od.giorno_consegna";

        $ordini_domicilio = array();
        $result_ordini_domicilio = mysqli_query($connection, $query_ordini_domicilio);
        while( $row = mysqli_fetch_assoc( $result_ordini_domicilio ) ){
            $ordini_domicilio[] = $row;
        }

        foreach ($ordini_domicilio as $key => $ordine) {
            $query_prodotti_ordinati = quick_select2(
                                        array("po.id","po.id_pietanza", "po.quantita", "po.note", "p.nome"),
                                        array("pietanze_ordinate po", "pietanze p"),
                                        array("p.id", "po.id_ordine"),
                                        array("po.id_pietanza", "\"$ordine[id_ordine]\"" ) );
            $prodotti_ordinati = array();
            $result_prodotti_ordinati = mysqli_query($connection, $query_prodotti_ordinati);
            while( $row = mysqli_fetch_assoc( $result_prodotti_ordinati ) ){
                // prendo gli ingredienti aggiunti e rimossi
                get_ingredienti_modificati( $connection, $row['id'], $row );
                $prodotti_ordinati[] = $row;
            }
            $ordini_domicilio[$key]['prodotti'] = $prodotti_ordinati;
        }
    ?>
    <div>
        <table class="table-orders">
            <tr class="table-row table-header">
                <th>ID ORDINE</th>
                <th>ID UTENTE</th>
                <th>INFO DI CONSEGNA</th>
                <th>ORARIO DI CONSEGNA</th>
                <th>INFO UTENTE</th>
                <th>DETTAGLI ORDINE</th>
            </tr>
                <?php
                foreach ( $ordini_domicilio as $key => $ordine ) {
                    echo "<tr class=\"table-row\">";
                        echo "<td>".$ordine["id_ordine"]."</td>";
                        echo "<td>".$ordine["id_utente"]."</td>";
                        echo "<td>";
                            echo "<details>";
                            echo "<summary>Clicca per visualizzare</summary>";
                                echo "<div>";
                                    echo "<div class=\"form-section\">";
                                        echo "<label class=\"form-field\">Indirizzo</label>";
                                        echo "<input class=\"form-field\" type=\"text\" disabled value=\"".$ordine['indirizzo']."\">";
                                    echo "</div>";
                                    echo "<div class=\"form-section\">";
                                        echo "<label class=\"form-field\">Citt&agrave</label>";
                                        echo "<input class=\"form-field\" type=\"text\" disabled value=\"".$ordine['citta']."\">";
                                    echo "</div>";
                                    echo "<div class=\"form-section\">";
                                        echo "<label class=\"form-field\">Comune</label>";
                                        echo "<input class=\"form-field\" type=\"text\" disabled value=\"".$ordine['comune']."\">";
                                    echo "</div>";
                                echo "</div>";
                                echo "<div class=\"clear\"></div>";
                            echo "</details>";
                        echo "</td>";
                        echo "<td>".explode(" ", $ordine["giorno_consegna"])[1]."</td>";
                        $utente = get_utente( $connection, $ordine["id_utente"] );
                        echo "<td>".$utente['cognome']." ".$utente['nome']."</td>";
                        echo "<td>";
                        echo "<details>";
                            echo "<summary>Clicca per visualizzare</summary>";
                            echo "<div class=\"left\">";
                                echo "<div name=\"section_prodotti\">";
                                    echo "<label>Prodotti richiesti</label>";
                                    echo "<select style=\"width: 100%;\"";
                                    $keys = array_keys( $ordine['prodotti'] );/*
                                    if( isset( $keys[0] ) ){
                                        // echo " selected=\"".$ordine['prodotti'][ $keys[0] ]['id']."\"";
                                    }*/
                                    echo ">";
                                    foreach ($ordine['prodotti'] as $key => $prodotto){
                                        echo "<option value=\"".$prodotto['id']."\"";
                                        if( $keys[0] == $key ){
                                            echo " selected='selected'";
                                        }

                                        echo ">";
                                            echo $prodotto['nome'];
                                        echo "</option>";
                                    }
                                    echo "</select>";
                                echo "</div>";
                                echo "<div name=\"section_note\">";
                                echo "<label>Note aggiuntive</label>";
                                foreach ($ordine['prodotti'] as $key => $prodotto) {
                                    echo "<textarea disabled value=\"".$prodotto['id']."\"style=\"min-width: 100%;max-width: 100%; display: none;\">".$prodotto['note']."</textarea>";
                                }
                                echo "</div>";
                            echo "</div>";
                            echo "<div class=\"left\">";
                                echo "<label>Quantit&agrave;</label>";
                                echo "<div name=\"section_quantita\">";
                                foreach ($ordine['prodotti'] as $key => $prodotto) {
                                    echo "<li value=\"".$prodotto['id']."\" style=\"display: none;\">";
                                        echo "<input disabled type='number' value=\"".$prodotto['quantita']."\">";
                                    echo "</li>";
                                }
                                echo "</div>";
                            echo "</div>";
                            echo "<div class=\"left\">";
                                echo "<div name=\"section_modifiche\" style=\"display: inline-block;\">";
                                    echo "<section style=\"display: inline-block; vertical-align: top;\">";
                                        echo "<label>Ingredienti aggiunti</label>";
                                        foreach ($ordine['prodotti'] as $key => $prodotto) {
                                            echo "<ul class=\"editable-list\" name=\"aggiunti\" value=\"".$prodotto['id']."\" style=\"display: none;\">";
                                            foreach ($prodotto['aggiunti'] as $key => $ingrediente) {
                                                echo "<li class=\"editable-item\" value=".$ingrediente['id'].">";
                                                    echo "<span class=\"editable-label\">";
                                                        echo "<label>".$ingrediente['nome']."</label>";
                                                    echo "</span>";
                                                echo "</li>";
                                            }
                                            echo "</ul>";
                                        }
                                    echo "</section>";
                                    echo "<section style=\"display: inline-block; vertical-align: top;\">";
                                        echo "<label>Ingredienti rimossi</label>";
                                        foreach ($ordine['prodotti'] as $key => $prodotto) {
                                            echo "<ul class=\"editable-list\" name=\"rimossi\" value=\"".$prodotto['id']."\" style=\"display: none;\">";
                                            foreach ($prodotto['rimossi'] as $key => $ingrediente) {
                                                echo "<li class=\"editable-item\" value=".$ingrediente['id'].">";
                                                    echo "<span class=\"editable-label\">";
                                                        echo "<label>".$ingrediente['nome']."</label>";
                                                    echo "</span>";
                                                echo "</li>";
                                            }
                                            echo "</ul>";
                                        }
                                    echo "</section>";
                                echo "</div>";
                            echo "</div>";
                            echo "<div class=\"clear\"></div>";
                        echo "</details>";
                        echo "</td>";
                    echo "</tr>";
                }
            ?>
        </table>
    </div>
</details>