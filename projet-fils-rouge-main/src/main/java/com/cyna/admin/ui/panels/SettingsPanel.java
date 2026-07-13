package com.cyna.admin.ui.panels;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.event.AncestorEvent;
import javax.swing.event.AncestorListener;

import com.cyna.admin.database.DBConnection;
import com.cyna.admin.ui.frames.MainFrame;

import java.awt.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

public class SettingsPanel extends JPanel {

    // 1 : ATTRIBUTS ET CONFIGURATION DU PANNEAU
    private MainFrame mainFrame;
    private JCheckBox chkDarkMode;
    private JCheckBox chkReseau;
    private JCheckBox chkAutoSave;

    public SettingsPanel(MainFrame mainFrame) {
        this.mainFrame = mainFrame;
        
        setLayout(new BorderLayout());
        setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);

        // Conteneur central des réglages
        JPanel panel = new JPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBackground(mainFrame.CLAIR_CONTENEUR);
        panel.setBorder(new EmptyBorder(40, 40, 40, 40));
        
        // 2 : COMPOSANTS DE L'INTERFACE GRAPHIQUE (UI)
        
        JLabel lblSettingsTitle = new JLabel("Paramètres");
        lblSettingsTitle.setFont(new Font("SansSerif", Font.BOLD, 18));
        lblSettingsTitle.setAlignmentX(Component.LEFT_ALIGNMENT);
        panel.add(lblSettingsTitle);
        panel.add(Box.createRigidArea(new Dimension(0, 20)));
        
        // Case Mode Sombre
        chkDarkMode = new JCheckBox("Mode sombre");
        chkDarkMode.setFont(new Font("SansSerif", Font.PLAIN, 14));
        chkDarkMode.setOpaque(false);
        chkDarkMode.setSelected(mainFrame.isDarkModeActive);
        chkDarkMode.setAlignmentX(Component.LEFT_ALIGNMENT);
        chkDarkMode.addActionListener(e -> mainFrame.applyDarkMode(chkDarkMode.isSelected()));
        panel.add(chkDarkMode);
        panel.add(Box.createRigidArea(new Dimension(0, 10)));

        // Case Réseau
        chkReseau = new JCheckBox("Réseau");
        chkReseau.setFont(new Font("SansSerif", Font.PLAIN, 14));
        chkReseau.setOpaque(false);
        chkReseau.setAlignmentX(Component.LEFT_ALIGNMENT);
        panel.add(chkReseau);
        panel.add(Box.createRigidArea(new Dimension(0, 10)));

        // Case Sauvegarde automatique
        chkAutoSave = new JCheckBox("Sauvegarde automatique");
        chkAutoSave.setFont(new Font("SansSerif", Font.PLAIN, 14));
        chkAutoSave.setOpaque(false);
        chkAutoSave.setAlignmentX(Component.LEFT_ALIGNMENT);
        chkAutoSave.addActionListener(e -> {
            // Sauvegarde immédiate de cette préférence dès qu'on clique dessus
            saveSettingToDB("autosave", chkAutoSave.isSelected() ? "1" : "0");
        });
        panel.add(chkAutoSave);
        panel.add(Box.createRigidArea(new Dimension(0, 25)));

        // Bouton de validation manuelle
        JButton btnSaveSettings = new JButton("Enregistrer les paramètres");
        btnSaveSettings.setMaximumSize(new Dimension(280, 35)); 
        btnSaveSettings.setAlignmentX(Component.LEFT_ALIGNMENT);
        btnSaveSettings.addActionListener(e -> {
            saveAllSettings();
            mainFrame.showCustomMessageDialog("Changements sauvegardés avec succès !");
        });
        panel.add(btnSaveSettings);

        add(panel, BorderLayout.NORTH);

        // 3 : ÉVÉNEMENTS DE NAVIGATION (LISTENERS)
        
        // Détecte quand l'utilisateur change d'onglet dans l'application administrative
        this.addAncestorListener(new AncestorListener() {
            @Override
            public void ancestorAdded(AncestorEvent event) {}

            @Override
            public void ancestorRemoved(AncestorEvent event) {
                // Si l'utilisateur quitte l'onglet Paramètres et que l'autosave est actif, on enregistre tout
                if (chkAutoSave.isSelected()) {
                    saveAllSettings();
                }
            }

            @Override
            public void ancestorMoved(AncestorEvent event) {}
        });

        // Chargement initial des configurations depuis la base MySQL
        loadSettingsFromDB();
    }

    // 4 : MÉTHODES DE SAUVEGARDE AUTOMATIQUE EXTERNE
    
    /**
     * Point d'ancrage appelé par la MainFrame juste avant la fermeture définitive de l'application.
     */
    public void autoSaveIfEnabled() {
        if (chkAutoSave != null && chkAutoSave.isSelected()) {
            saveAllSettings();
        }
    }

    // 5 : ACCÈS À LA BASE DE DONNÉES (CHARGEMENT & REQUÊTES)
    
    /**
     * Récupère l'ensemble des configurations stockées dans la table 'reglages'.
     */
    private void loadSettingsFromDB() {
        String query = "SELECT cle, valeur FROM reglages";
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query);
             ResultSet rs = ps.executeQuery()) {
             
            while (rs.next()) {
                String cle = rs.getString("cle");
                String valeur = rs.getString("valeur");
                
                // Association des valeurs binaires ("1" ou "0") aux composants Swing
                switch (cle) {
                    case "dark_mode":
                        chkDarkMode.setSelected("1".equals(valeur));
                        break;
                    case "reseau":
                        chkReseau.setSelected("1".equals(valeur));
                        break;
                    case "autosave":
                        chkAutoSave.setSelected("1".equals(valeur));
                        break;
                }
            }
        } catch (Exception e) {
            System.out.println("Aucun réglage trouvé ou erreur BDD. Valeurs par défaut conservées.");
        }
    }

    /**
     * Envoie l'intégralité des états actuels des composants vers la base de données.
     */
    private void saveAllSettings() {
        saveSettingToDB("dark_mode", chkDarkMode.isSelected() ? "1" : "0");
        saveSettingToDB("reseau", chkReseau.isSelected() ? "1" : "0");
        saveSettingToDB("autosave", chkAutoSave.isSelected() ? "1" : "0");
    }

    /**
     * Exécute la requête SQL d'insertion ou de mise à jour (Upsert).
     */
    private void saveSettingToDB(String cle, String valeur) {
        // ASTUCE SQL : S'il s'agit d'une nouvelle clé, elle est insérée. Si elle existe déjà (clé primaire/unique), elle est mise à jour.
        String query = "INSERT INTO reglages (cle, valeur) VALUES (?, ?) ON DUPLICATE KEY UPDATE valeur = ?";
        
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setString(1, cle);
            ps.setString(2, valeur);
            ps.setString(3, valeur);
            ps.executeUpdate();
        } catch (Exception e) {
            e.printStackTrace(); 
        }
    }
}