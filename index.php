<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" href="style.css">
        <title>Zadanie rekrutacyjne</title>
    </head>
    
    <body>
        <div id="main">
            <form action="index.php" method="get">
                Dane wejsciowe. Format: "ZieLoNa MiLa|age>30"<input name="Dane_wejsciowe"><br>
            </from>
            <?php
                function Pokaz_tytul( $dane_wejsciowe )
                {
                    $nazwa_serwera = "localhost";   // Nazwa serwera pracującego lokalnie
                    $nazwa_uzytkownika = "root";    // Domyślne konto z pełnymi uprawnieniami
                    $haslo = "";                    // Domyślnie brak hasła dla konta root
                    $DB = "zadanie_rekrutacyjne";   // Nazwa bazy danych

                    // Tworzenie połączenia z bazą danych
                    $polaczenie = mysqli_connect( $nazwa_serwera, $nazwa_uzytkownika, $haslo, $DB );
                    if( !$polaczenie ) // Zabezpieczenie przed niepoprawnym połączeniem
                    {
                        die( "Nie połączono się z bazą danych.<br>" );
                    }

                    $polaczenie->set_charset( "UTF8" );
                    
                    if( isset( $dane_wejsciowe ) )
                    {
                        $wyrazenie = preg_match( '/^([a-z|A-Z|0-9]{1, 56})|\|age(\<|\>)([0-9]{1,11})$/', $dane_wejsciowe );
                        
                        if( $dane_wejsciowe == $wyrazenie )
                        {
                            echo "Podana nazwa nie jest zgodna z formatem wprowadzania.<br>";
                        }
                        else
                        {
                            $tytul_ksiazki_podany_przez_uzytkownika = substr( $dane_wejsciowe, 0, strpos( $dane_wejsciowe, "|" ) ); // Wszystkie znaki do znaku "|"
                            $mniejsze = strpos( $dane_wejsciowe, "<" ); // Szukamy znaku "<"
                            
                            if( $mniejsze == true )
                            {
                                $wiek_pomocnicza = strchr( $dane_wejsciowe, "<" );
                                $wiek = substr( $wiek_pomocnicza, 1 ); // Wiek podany przez użytkownika
                            }
                            else
                            {
                                $wiek_pomocnicza = strchr( $dane_wejsciowe, ">" );
                                $wiek = substr( $wiek_pomocnicza, 1 ); // Wiek podany przez użytkownika
                            }

                            echo "<table>";
                            echo "<tr>";
                            echo "<th>Book</th>";
                            echo "<th>Compatibility</th>";
                            echo "<th>Book Date</th>";
                            echo "<th>Female AVG age</th>";
                            echo "<th>Male AVG age</th>";
                            echo "</tr>";

                            if( $mniejsze == true )
                            {
                                $zapytanie_SQL = "SELECT books.name, books.book_date, reviews.age, reviews.sex FROM books, reviews WHERE books.id = reviews.book_id AND books.name = '".$tytul_ksiazki_podany_przez_uzytkownika."' AND reviews.age < $wiek;";
                            }
                            else
                            {
                                $zapytanie_SQL = "SELECT books.name, books.book_date, reviews.age, reviews.sex FROM books, reviews WHERE books.id = reviews.book_id AND books.name = '".$tytul_ksiazki_podany_przez_uzytkownika."' AND reviews.age > $wiek;";
                            }

                            $wynik_zapytania = mysqli_query( $polaczenie, $zapytanie_SQL );

                            if( mysqli_num_rows( $wynik_zapytania ) > 0 ) // Gdy tytuł książki jest w bazie danych i czytelnik wypożyczył daną książkę o podanym tytule
                            {
                                $tytul_DB;
                                $data_DB;
                                $kobiety_pomocnicza   = 0;
                                $mezczyzna_pomocnicza = 0;
                                $licznik_kobiet       = 0;
                                $licznik_mezczyzn     = 0;
                                $wynik_wieku_kobiet   = 0;
                                $wynik_wieku_mezczyzn = 0;

                                while( $wiersz = mysqli_fetch_row( $wynik_zapytania ) )
                                {
                                    $tytul_DB = $wiersz[ 0 ];
                                    $data_DB  = $wiersz[ 1 ];
                                    
                                    if( $wiersz[ 3 ] == "f" )
                                    {
                                        $licznik_kobiet++;
                                        $kobiety_pomocnicza += $wiersz[ 2 ];
                                    }
                                    else
                                    {
                                        $licznik_mezczyzn++;
                                        $mezczyzna_pomocnicza += $wiersz[ 2 ];
                                    }
                                }

                                echo "<tr>";
                                echo "<td>".$tytul_DB."</td>";
                                echo "<td>100 %</td>";
                                echo "<td>".$data_DB."</td>";
                                
                                if( $licznik_kobiet == 0 )
                                {
                                    echo "<td>0</td>";
                                }
                                else
                                {
                                    @$wynik_wieku_kobiet = $kobiety_pomocnicza / $licznik_kobiet++;
                                    echo "<td>".$wynik_wieku_kobiet."</td>";
                                }
                                if( $licznik_mezczyzn == 0 )
                                {
                                    echo "<td>0</td>";
                                }
                                else
                                {
                                    @$wynik_wieku_mezczyzn = $mezczyzna_pomocnicza / $licznik_mezczyzn++;
                                    echo "<td>".$wynik_wieku_mezczyzn."</td>";
                                }

                                echo "</tr>";
                            }
                            else // Gdy podanego tytułu nie ma w bibliotece lub nie została wypożyczona. Wyświetla wszystkie wypożyczone książki z podanym współczynnikiem prawdopodobieństwa
                            {
                                $tytul_DB  = [];
                                $data_DB   = [];
                                $srednia_wieku_kobiet   = [];
                                $srednia_wieku_mezczyzn = [];
                                $wspolczynnik_prawdopodobienstwa = [];

                                // Zapytanie pomocnicze wyświetlające wszystkie tytuły książek, które zostały wypożyczone
                                if( $mniejsze == true )
                                {
                                    $zapytanie_SQL_pomocnicze = "SELECT books.name, books.book_date, AVG( reviews.age ) as averageAge, reviews.sex FROM books, reviews WHERE books.id = reviews.book_id AND reviews.age < $wiek GROUP BY books.name;"; // Na tym etapie pracy dowiedziałem się o wbudowanej funkcji "AVG( reviews.age ) as averageAge"
                                }
                                else
                                {
                                    $zapytanie_SQL_pomocnicze = "SELECT books.name, books.book_date, AVG( reviews.age ) as averageAge, reviews.sex FROM books, reviews WHERE books.id = reviews.book_id AND reviews.age > $wiek GROUP BY books.name;"; // Na tym etapie pracy dowiedziałem się o wbudowanej funkcji "AVG( reviews.age ) as averageAge"
                                }

                                $wynik_zapytania_pomocniczego = mysqli_query( $polaczenie, $zapytanie_SQL_pomocnicze );
                                
                                while( $wiersz = mysqli_fetch_row( $wynik_zapytania_pomocniczego ) )
                                {
                                    array_push( $tytul_DB, $wiersz[ 0 ] );
                                    array_push( $data_DB, $wiersz[ 1 ]  );
                                    
                                    //*
                                    // Aby uzyskac najbardziej zblizony rezultat do wynikow z "wynik działania Listing 2" z zadania rekrutacyjnego należy zamienić tytul ksiazki podany przez użytkownika na małe litery, tytuł książek w bazie danych na małe litery i wyłączeniu polskiego kodowania dla podanego tytułu książki przez użytkownika i tyułu książki w bazie danych
                                    // W "wynik działania Listing 2" z zadania rekrutacyjnego tytuł książki "Na szafocie" według mnie ma niepoprawnie obliczony wsółczynnik prawdopodobieństwa, ponieważ aby wyszło 25% należy porównać "Na szafocie" ale wtedy na przykład współczynnik prawdopodobieństwa "Co nam zostało" jest różny od wyniku z "wynik działania Listing 2"
                                    $brak_polskich_znakow_dla_tytulu_ksiazek_podanych_przez_uzytkownika = iconv( "UTF-8", "ISO-8859-2", $tytul_ksiazki_podany_przez_uzytkownika );
                                    $male_litery_brak_polskich_znakow_dla_tytulu_ksiazek_podanego_przez_uzytkownika = strtolower( $brak_polskich_znakow_dla_tytulu_ksiazek_podanych_przez_uzytkownika );
                                    
                                    $brak_polskich_znakow_dla_tytulu_ksiazek_w_DB = iconv( "UTF-8", "ISO-8859-2", $wiersz[ 0 ] );
                                    $male_litery_brak_polskich_znakow_dla_tytulu_ksiazek_w_DB = strtolower( $brak_polskich_znakow_dla_tytulu_ksiazek_w_DB );
                                    
                                    similar_text( $male_litery_brak_polskich_znakow_dla_tytulu_ksiazek_w_DB, $male_litery_brak_polskich_znakow_dla_tytulu_ksiazek_podanego_przez_uzytkownika, $procent_wspolczynnika_prawdopodobienstwa );
                                    $procent_wspolczynnika_prawdopodobienstwa = round( $procent_wspolczynnika_prawdopodobienstwa, 2 ); // Zaokrąglenie do dwóch miejsc po przecinku
                                    array_push( $wspolczynnik_prawdopodobienstwa, $procent_wspolczynnika_prawdopodobienstwa );
                                    //*/

                                    /*
                                    // Dla polskich znaków. Powyższe 14 linijek należy zakomentować i odkomentować poniższe do klamry kończące pętle "while" 
                                    similar_text( $wiersz[ 0 ], $tytul_ksiazki_podany_przez_uzytkownika, $procent_wspolczynnika_prawdopodobienstwa );
                                    $procent_wspolczynnika_prawdopodobienstwa = round( $procent_wspolczynnika_prawdopodobienstwa, 2 ); // Zaokrąglenie do dwóch miejsc po przecinku
                                    array_push( $wspolczynnik_prawdopodobienstwa, $procent_wspolczynnika_prawdopodobienstwa );
                                    */
                                }

                                // Zapytania dla kobiet
                                if( $mniejsze == true )
                                {
                                    $zapytanie_SQL_dla_kobiet = "SELECT books.name, books.book_date, AVG( reviews.age ) as averageAge, reviews.sex FROM books, reviews WHERE books.id = reviews.book_id AND reviews.age < $wiek AND reviews.sex = 'f' GROUP BY books.name;"; // Na tym etapie pracy dowiedziałem się o wbudowanej funkcji "AVG( reviews.age ) as averageAge"
                                }
                                else
                                {
                                    $zapytanie_SQL_dla_kobiet = "SELECT books.name, books.book_date, AVG( reviews.age ) as averageAge, reviews.sex FROM books, reviews WHERE books.id = reviews.book_id AND reviews.age > $wiek AND reviews.sex = 'f' GROUP BY books.name;"; // Na tym etapie pracy dowiedziałem się o wbudowanej funkcji "AVG( reviews.age ) as averageAge"
                                }

                                $wynik_zapytania_dla_kobiet = mysqli_query( $polaczenie, $zapytanie_SQL_dla_kobiet );
                                
                                $ile = 0;

                                while( $wiersz = mysqli_fetch_row( $wynik_zapytania_dla_kobiet ) )
                                {
                                    if( $tytul_DB[ $ile ] == $wiersz[ 0 ] )
                                    {
                                        array_push( $srednia_wieku_kobiet, $wiersz[ 2 ] );
                                    }
                                    else
                                    {
                                        for( $i = 0; $i < count( $tytul_DB ); $i++ )
                                        {
                                            if( $tytul_DB[ $i ] == $wiersz[ 0 ] )
                                            {
                                                $roznica =  $i - count( $srednia_wieku_kobiet );
                                            }
                                        }
                                        for( $i = 0; $i < $roznica; $i++ )
                                        {
                                            array_push( $srednia_wieku_kobiet, 0 ); 
                                        }
                                        array_push( $srednia_wieku_kobiet, $wiersz[ 2 ] );
                                        $ile++;
                                    }
                                    $ile++;
                                }

                                // Zapytania dla mężczyzn
                                if( $mniejsze == true )
                                {
                                    $zapytanie_SQL_mezczyzn = "SELECT books.name, books.book_date, AVG( reviews.age ) as averageAge, reviews.sex FROM books, reviews WHERE books.id = reviews.book_id AND reviews.age < $wiek AND reviews.sex = 'm' GROUP BY books.name;"; // Na tym etapie pracy dowiedziałem się o wbudowanej funkcji "AVG( reviews.age ) as averageAge"
                                }
                                else
                                {
                                    $zapytanie_SQL_mezczyzn = "SELECT books.name, books.book_date, AVG( reviews.age ) as averageAge, reviews.sex FROM books, reviews WHERE books.id = reviews.book_id AND reviews.age > $wiek AND reviews.sex = 'm' GROUP BY books.name;"; // Na tym etapie pracy dowiedziałem się o wbudowanej funkcji "AVG( reviews.age ) as averageAge"
                                }
                                
                                $wynik_zapytania_dla_mezczyzn = mysqli_query( $polaczenie, $zapytanie_SQL_mezczyzn );
                                $ile = 0;
                                
                                while( $wiersz = mysqli_fetch_row( $wynik_zapytania_dla_mezczyzn ) )
                                {
                                    if( $tytul_DB[ $ile ] == $wiersz[ 0 ] )
                                    {
                                        array_push( $srednia_wieku_mezczyzn, $wiersz[ 2 ] );
                                    }
                                    else
                                    {
                                        for( $i = 0; $i < count( $tytul_DB ); $i++ )
                                        {
                                            if( $tytul_DB[ $i ] == $wiersz[ 0 ] )
                                            {
                                                $roznica =  $i - count( $srednia_wieku_mezczyzn );
                                            }
                                        }
                                        for( $i = 0; $i < $roznica; $i++ )
                                        {
                                            array_push( $srednia_wieku_mezczyzn, 0 ); 
                                        }
                                        array_push( $srednia_wieku_mezczyzn, $wiersz[ 2 ] );
                                        $ile++;
                                    }
                                    $ile++;
                                }
                                
                                // Zabezpieczenie przed pustą komórką ( wiek kobiet ), gdzie wstawiamy zero.
                                if( count( $tytul_DB ) != count( $srednia_wieku_kobiet ) )
                                {
                                    $roznica = count( $tytul_DB ) - count( $srednia_wieku_kobiet );
                                    for( $i = 0; $i < $roznica; $i++ )
                                    {
                                        array_push( $srednia_wieku_kobiet, 0 ); 
                                    } 
                                }
                                // Zabezpieczenie przed pustą komórką ( wiek mężczyzn ), gdzie wstawiamy zero.
                                if( count( $tytul_DB ) != count( $srednia_wieku_mezczyzn ) )
                                {
                                    $roznica = count( $tytul_DB ) - count( $srednia_wieku_mezczyzn );
                                
                                    for( $i = 0; $i < $roznica; $i++ )
                                    {
                                        array_push( $srednia_wieku_mezczyzn, 0 ); 
                                    }
                                }

                                // Wyświetlanie wyników
                                for( $i = 0; $i < count( $tytul_DB ); $i++ )
                                {
                                    echo "<tr>";
                                    echo "<td>".$tytul_DB[ $i ]."</td>";
                                    echo "<td>".$wspolczynnik_prawdopodobienstwa[ $i ]." %</td>";
                                    echo "<td>".$data_DB[ $i ]."</td>";                        
                                    echo "<td>".round( $srednia_wieku_kobiet[ $i ],   2 )."</td>";
                                    echo "<td>".round( $srednia_wieku_mezczyzn[ $i ], 2 )."</td>";
                                    echo "<tr>";
                                }
                            }
                            echo "</table>";
                        }
                    }
                    mysqli_close( $polaczenie );
                }

                echo "Wynik działania Listing 1.<br>";
                Pokaz_tytul( "ZieLoNa MiLa|age>30" );  // "wynik działania Listing 1"   // Wynik na "wynik działania Listing 1" dla średniej wieku kobiet jest niepoprawna, ponieważ średnia wieku kobiet dla tytułu książki "Zielona Mila" i wieku większym niż 30 wynosi 48 a nie 38 tak jak na "wynik działania Listing 1" z zadania rekrutacyjnego. W bazie danych są dwie kobiety, które wypożyczyły tą książkę i ich wiek wynosi 38 i 58 lat więc średnia wieku wynosi 48 ( ( 38 + 58 ) / 2 = 48 ).
                echo "<br>Wynik działania Listing 2.<br>";
                Pokaz_tytul( "ZiElonA Droga|age<30" ); // "wynik działania Listing 2"
                echo "<br>Wynik działania podany przez użytkownika.<br>";
                @Pokaz_tytul( $_GET[ "Dane_wejsciowe" ] ); // Wynik działania podany przez użytkownika
            ?>
        </div>
    </body>
</html>