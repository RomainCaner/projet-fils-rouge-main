package com.cyna.admin.ui.panels;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.event.DocumentEvent;
import javax.swing.event.DocumentListener;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableRowSorter;

import com.cyna.admin.database.DBConnection;
import com.cyna.admin.ui.frames.MainFrame;

import java.awt.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

/**
 * Panneau de consultation des abonnements clients.
 *
 * Vue en lecture seule (la gestion du cycle de vie — résiliation, réactivation,
 * renouvellement — se fait côté site web, dans l'espace client). Ce panneau
 * reprend les mêmes informations que la page « Abonnements » du back-office web :
 * client, service, périodicité, échéance, renouvellement automatique et statut,
 * avec recherche filtrée en temps réel.
 */
public class SubscriptionsPanel extends JPanel {

    // 1 : ATTRIBUTS ET CONFIGURATION DU PANNEAU
    private MainFrame mainFrame;
    private DefaultTableModel subTableModel;
    private JTable subTable;
    private TableRowSorter<DefaultTableModel> subSorter;

    public SubscriptionsPanel(MainFrame mainFrame) {
        this.mainFrame = mainFrame;
        setLayout(new BorderLayout(0, 15));
        setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
        setBorder(new EmptyBorder(20, 30, 20, 30));

        // 2 : BARRE SUPÉRIEURE (RECHERCHE FILTRÉE ET RAFRAÎCHISSEMENT)
        JPanel topPanel = new JPanel(new BorderLayout());
        topPanel.setOpaque(false);

        JPanel searchPanel = new JPanel(new FlowLayout(FlowLayout.LEFT));
        searchPanel.setOpaque(false);

        JLabel lblSearch = new JLabel("🔍 Rechercher Abonnement :");
        lblSearch.setForeground(mainFrame.isDarkModeActive ? Color.WHITE : mainFrame.CLAIR_TEXTE);
        searchPanel.add(lblSearch);

        JTextField searchField = new JTextField(25);
        if (mainFrame.isDarkModeActive) {
            searchField.setBackground(mainFrame.SOMBRE_CONTENEUR.brighter());
            searchField.setForeground(Color.WHITE);
            searchField.setCaretColor(Color.WHITE);
        }
        searchPanel.add(searchField);

        JPanel actionBar = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        actionBar.setOpaque(false);

        JButton btnRefresh = new JButton("Rafraîchir");
        btnRefresh.addActionListener(e -> loadSubscriptionsFromDB());
        actionBar.add(btnRefresh);

        topPanel.add(searchPanel, BorderLayout.WEST);
        topPanel.add(actionBar, BorderLayout.EAST);

        // 3 : CONFIGURATION DU TABLEAU (LECTURE SEULE)
        String[] cols = new String[]{"ID_DB", "Client", "Service", "Périodicité", "Qté", "Échéance", "Renouv. auto", "Statut"};

        subTableModel = new DefaultTableModel(new Object[0][8], cols) {
            @Override
            public boolean isCellEditable(int r, int c) {
                return false; // Vue de consultation : aucune édition directe.
            }
        };

        subTable = new JTable(subTableModel);
        subTable.setRowHeight(35);
        subTable.setGridColor(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : mainFrame.CLAIR_GRILLE);
        subTable.setSelectionBackground(mainFrame.CYNA_BLEU);
        subTable.setSelectionForeground(Color.WHITE);

        if (mainFrame.isDarkModeActive) {
            subTable.setBackground(mainFrame.SOMBRE_CONTENEUR);
            subTable.setForeground(Color.WHITE);
            subTable.getTableHeader().setBackground(mainFrame.SOMBRE_CONTENEUR.darker());
            subTable.getTableHeader().setForeground(Color.WHITE);
        } else {
            subTable.setBackground(Color.WHITE);
            subTable.setForeground(Color.BLACK);
            subTable.getTableHeader().setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
            subTable.getTableHeader().setForeground(Color.BLACK);
        }

        // Dissimulation visuelle de la colonne ID technique (index 0 du modèle).
        subTable.getColumnModel().removeColumn(subTable.getColumnModel().getColumn(0));

        // Moteur de tri et de filtrage en temps réel.
        subSorter = new TableRowSorter<>(subTableModel);
        subTable.setRowSorter(subSorter);

        searchField.getDocument().addDocumentListener(new DocumentListener() {
            private void filter() { subSorter.setRowFilter(RowFilter.regexFilter("(?i)" + searchField.getText())); }
            @Override public void insertUpdate(DocumentEvent e) { filter(); }
            @Override public void removeUpdate(DocumentEvent e) { filter(); }
            @Override public void changedUpdate(DocumentEvent e) { filter(); }
        });

        add(topPanel, BorderLayout.NORTH);

        JScrollPane scrollPane = new JScrollPane(subTable);
        scrollPane.getViewport().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : Color.WHITE);
        add(scrollPane, BorderLayout.CENTER);

        loadSubscriptionsFromDB();
    }

    // 4 : ACCÈS À LA BASE DE DONNÉES (LECTURE)
    private void loadSubscriptionsFromDB() {
        subTableModel.setRowCount(0);
        String query =
            "SELECT a.id, u.email AS client, a.produit_nom, a.periodicite, a.quantite, " +
            "a.statut, a.renouvellement_auto, a.renouvelle_le " +
            "FROM abonnements a LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id " +
            "ORDER BY a.debute_le DESC";

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query);
             ResultSet rs = ps.executeQuery()) {

            while (rs.next()) {
                int id = rs.getInt("id");
                String client = rs.getString("client");
                if (client == null) client = "—";
                String service = rs.getString("produit_nom");
                String periode = "annual".equalsIgnoreCase(rs.getString("periodicite")) ? "Annuel" : "Mensuel";
                int quantite = rs.getInt("quantite");
                String echeance = formatDate(rs.getString("renouvelle_le"));
                String renouvAuto = rs.getInt("renouvellement_auto") == 1 ? "Oui" : "Non";
                String statut = mapStatutToUI(rs.getString("statut"));

                subTableModel.addRow(new Object[]{id, client, service, periode, quantite, echeance, renouvAuto, statut});
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    // 5 : UTILITAIRES D'AFFICHAGE
    private String mapStatutToUI(String dbStatut) {
        if (dbStatut == null) return "—";
        switch (dbStatut.toLowerCase()) {
            case "active":    return "Actif";
            case "cancelled": return "Résilié";
            case "expired":   return "Expiré";
            default:           return dbStatut;
        }
    }

    /** Convertit une date SQL (yyyy-MM-dd HH:mm:ss) au format JJ/MM/AAAA. */
    private String formatDate(String sqlDate) {
        if (sqlDate == null || sqlDate.length() < 10) return "—";
        String d = sqlDate.substring(0, 10); // yyyy-MM-dd
        return d.substring(8, 10) + "/" + d.substring(5, 7) + "/" + d.substring(0, 4);
    }
}
