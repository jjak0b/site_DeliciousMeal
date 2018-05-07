<?php
    include_once('../config.php' );
    include_once('../scripts/utility.php' );
    include_once('../scripts/shared_site.php' );
    
    if( !isset( $connection ) ){
        $connection = mysqli_connect(HOST, USER, PASSWORD, DB_NAME );
    }

    // Check connection
    if ( mysqli_connect_errno() )
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
?>
<div>
    <div>
        <details style="text-align: left; display: block; width: 100%;">
            <summary>Ordini locale</summary>
            <?php
                $tavolo;
                $id_utente;
                $query_ordini_locale = "SELECT ol.id as id_ordine, ol.tavolo, o.id_utente
                                        FROM ordini_locale ol, ordini o
                                        WHERE ol.id = o.id";
                if( isset( $id_utente ) ){
                    $query_ordini_locale .= " AND o.id_utente = '$id_utente'";
                }
                if( isset( $tavolo ) ){
                    $query_ordini_locale .= " AND ol.tavolo = '$tavolo'";
                }

                $ordini_locale = array();
                $result_ordini_locale = mysqli_query($connection, $query_ordini_locale);
                while( $row = mysqli_fetch_assoc( $result_ordini_locale ) ){
                    $ordini_locale[] = $row;
                }

                foreach ($ordini_locale as $key => $ordine) {
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
                    $ordini_locale[$key]['prodotti'] = $prodotti_ordinati;
                }
            ?>
            <div>
                <table>
                    <tr>
                        <th>ID ORDINE</th>
                        <th>ID UTENTE</th>
                        <th>TAVOLO</th>
                        <th>INFO UTENTE</th>
                        <th>DETTAGLI ORDINE</th>
                    </tr>
                        <?php
                        foreach ( $ordini_locale as $key => $ordine ) {
                            echo "<tr>";
                                echo "<td>".$ordine["id_ordine"]."</td>";
                                echo "<td>".$ordine["id_utente"]."</td>";
                                echo "<td>".$ordine["tavolo"]."</td>";
                                $utente = get_utente( $connection, $ordine["id_utente"] );
                                echo "<td>".$utente['cognome']." ".$utente['nome']."</td>";
                                echo "<td>";
                                echo "<details>";
                                    echo "<summary>clicca per visualizzare</summary>";
                                    echo "<div class=\"left\">";
                                        echo "<div id=\"section_prodotti\">";
                                            echo "<select style=\"width: 100%;\">";
                                            foreach ($ordine['prodotti'] as $key => $prodotto){
                                                echo "<option value=".$prodotto['id'].">";
                                                    echo $prodotto['nome'];
                                                echo "</option>";
                                            }
                                            echo "</select>";
                                        echo "</div>";
                                        echo "<div id=\"section_note\">";
                                            echo "<textarea disabled></textarea>";
                                        echo "</div>";
                                    echo "</div>";
                                    echo "<div class=\"left\">";
                                        echo "<div id=\"section_modifiche\" style=\"display: inline-block;\">";
                                            echo "<section style=\"display: inline-block; vertical-align: top;\">";
                                                echo "<label>Ingredienti aggiunti</label>";
                                                echo "<ul id=\"aggiunti\" class=\"editable-list\" name=\"aggiunti\">";
                                                foreach ($prodotto['aggiunti'] as $key => $ingrediente) {
                                                    echo "<li class=\"editable-item\" value=".$row['id'].">";
                                                        echo "<div>";
                                                            echo "<div class=\"editable-label\">";
                                                                echo "<label>".$ingrediente['nome']."</label>";
                                                            echo "</div>";
                                                        echo "</div>";
                                                    echo "</li>";
                                                }
                                                echo "</ul>";
                                            echo "</section>";
                                            echo "<section style=\"display: inline-block; vertical-align: top;\">";
                                                echo "<label>Ingredienti rimossi</label>";
                                                echo "<ul id=\"rimossi\" class=\"editable-list\" name=\"rimossi\">";
                                                foreach ($prodotto['rimossi'] as $key => $ingrediente) {
                                                    echo "<li class=\"editable-item\" value=".$row['id'].">";
                                                        echo "<div>";
                                                            echo "<div class=\"editable-label\">";
                                                                echo "<label>".$ingrediente['nome']."</label>";
                                                            echo "</div>";
                                                        echo "</div>";
                                                    echo "</li>";
                                                }
                                                echo "</ul>";
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
    </div>
    <div>
        <details style="text-align: left; display: block; width: 100%;">
            <summary>Ordini a domicilio</summary>
            <table>
            </table>
        </details>
    </div>
</div>