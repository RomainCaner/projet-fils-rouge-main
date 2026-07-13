package com.cyna.admin.database;

import java.io.InputStream;
import java.sql.Connection;
import java.sql.DriverManager;
import java.util.Properties;

public class DBConnection {
    private static String url;
    private static String user;
    private static String password;

    // Le bloc statique s'exécute une seule fois au chargement de la classe
    static {
        try (InputStream input = DBConnection.class.getClassLoader().getResourceAsStream("application.properties")) {
            Properties prop = new Properties();
            if (input == null) {
                System.out.println("Erreur : Impossible de trouver le fichier application.properties");
            } else {
                // Charger le fichier de configuration
                prop.load(input);
                
                // Récupérer les données de connexion
                url = prop.getProperty("spring.datasource.url");
                user = prop.getProperty("spring.datasource.username");
                password = prop.getProperty("spring.datasource.password");
                
                // Charger le driver JDBC MySQL
                Class.forName(prop.getProperty("spring.datasource.driver-class-name"));
            }
        } catch (Exception ex) {
            System.out.println("Erreur lors de l'initialisation de la base de données :");
            ex.printStackTrace();
        }
    }

    /**
     * Permet de récupérer une connexion active vers la base de données MySQL.
     * @return Connection object
     * @throws Exception
     */
    public static Connection getConnection() throws Exception {
        if (url == null || user == null || password == null) {
            throw new IllegalStateException("La configuration de la base de données n'a pas été chargée correctement.");
        }
        return DriverManager.getConnection(url, user, password);
    }
}