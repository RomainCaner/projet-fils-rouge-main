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
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Pattern;

public class SupportPanel extends JPanel {

    // 1 : ATTRIBUTS ET CONFIGURATION DU PANNEAU
    private MainFrame mainFrame;
    private DefaultTableModel supportTableModel;
    private JTable supportTable;
    private TableRowSorter<DefaultTableModel> sorter;

    // Filtres
    private JComboBox<String> filterStatus;
    private JTextField filterSearch;

    // Rafraîchissement temps réel
    private Timer refreshTimer;
    private int lastKnownCount = -1;
    private long lastKnownMaxId = -1;

    public SupportPanel(MainFrame mainFrame) {
        this.mainFrame = mainFrame;

        // Initialisation du layout et du design général
        setLayout(new BorderLayout(0, 15));
        setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
        setBorder(new EmptyBorder(20, 30, 20, 30));

        // 2 : BARRE SUPÉRIEURE (TITRE ET BOUTONS D'ACTION)
        JPanel topPanel = new JPanel(new BorderLayout());
        topPanel.setOpaque(false);

        JPanel infoPanel = new JPanel(new FlowLayout(FlowLayout.LEFT, 10, 0));
        infoPanel.setOpaque(false);
        JLabel info = new JLabel("Messages du formulaire de contact :");
        info.setFont(new Font("SansSerif", Font.BOLD, 14));
        info.setForeground(mainFrame.CLAIR_TEXTE);
        infoPanel.add(info);
        // Indicateur « temps réel »
        JLabel liveIndicator = new JLabel("● Temps réel");
        liveIndicator.setFont(new Font("SansSerif", Font.BOLD, 12));
        liveIndicator.setForeground(new Color(16, 185, 129)); // Vert CYNA
        liveIndicator.setToolTipText("La liste s'actualise automatiquement à l'arrivée de nouveaux messages.");
        infoPanel.add(liveIndicator);

        JPanel actionBar = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        actionBar.setOpaque(false);

        // Bouton Rafraîchir (manuel)
        JButton btnRefresh = new JButton("Rafraîchir");
        btnRefresh.addActionListener(e -> loadMessagesFromDB());
        actionBar.add(btnRefresh);

        // Bouton Modifier
        JButton btnEdit = new JButton("Voir / Modifier");
        btnEdit.addActionListener(e -> {
            int row = supportTable.getSelectedRow();
            if (row != -1) openSupportEditDialog(row);
            else mainFrame.showCustomMessageDialog("Sélectionnez un message.");
        });
        actionBar.add(btnEdit);

        // Bouton Supprimer
        JButton btnDelete = new JButton("Supprimer");
        btnDelete.setForeground(Color.RED);
        btnDelete.addActionListener(e -> {
            int viewRowIndex = supportTable.getSelectedRow();
            if (viewRowIndex != -1) {
                int confirm = mainFrame.showCustomConfirmDialog(
                    "Voulez-vous vraiment supprimer ce message ?",
                    "Confirmation"
                );

                if (confirm == JOptionPane.YES_OPTION) {
                    // Sécurité : Conversion de l'index visuel (vue) vers l'index réel (modèle)
                    int modelRowIndex = supportTable.convertRowIndexToModel(viewRowIndex);
                    int idDb = (int) supportTableModel.getValueAt(modelRowIndex, 0);

                    deleteMessageFromDB(idDb);
                    loadMessagesFromDB();
                }
            } else {
                mainFrame.showCustomMessageDialog("Sélectionnez un message à supprimer.");
            }
        });
        actionBar.add(btnDelete);

        topPanel.add(infoPanel, BorderLayout.WEST);
        topPanel.add(actionBar, BorderLayout.EAST);

        // 2bis : BARRE DE FILTRES (statut + recherche)
        JPanel filterPanel = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 8));
        filterPanel.setOpaque(false);

        JLabel lblStatus = new JLabel("Statut :");
        lblStatus.setForeground(mainFrame.CLAIR_TEXTE);
        filterPanel.add(lblStatus);
        filterStatus = new JComboBox<>(new String[]{"Tous", "NOUVEAU", "LU", "ARCHIVÉ"});
        filterStatus.addActionListener(e -> applyFilter());
        filterPanel.add(filterStatus);

        filterPanel.add(Box.createRigidArea(new Dimension(15, 0)));

        JLabel lblSearch = new JLabel("Recherche :");
        lblSearch.setForeground(mainFrame.CLAIR_TEXTE);
        filterPanel.add(lblSearch);
        filterSearch = new JTextField(22);
        filterSearch.setToolTipText("Filtre par e-mail, sujet ou contenu du message.");
        filterSearch.getDocument().addDocumentListener(new DocumentListener() {
            public void insertUpdate(DocumentEvent e) { applyFilter(); }
            public void removeUpdate(DocumentEvent e) { applyFilter(); }
            public void changedUpdate(DocumentEvent e) { applyFilter(); }
        });
        filterPanel.add(filterSearch);

        // Bouton de réinitialisation des filtres
        JButton btnClear = new JButton("Réinitialiser");
        btnClear.addActionListener(e -> {
            filterStatus.setSelectedIndex(0);
            filterSearch.setText("");
            applyFilter();
        });
        filterPanel.add(btnClear);

        // Conteneur d'en-tête regroupant la barre supérieure et les filtres
        JPanel header = new JPanel(new BorderLayout());
        header.setOpaque(false);
        header.add(topPanel, BorderLayout.NORTH);
        header.add(filterPanel, BorderLayout.SOUTH);
        add(header, BorderLayout.NORTH);

        // 3 : CRÉATION ET CONFIGURATION GRAPHIQUE DU TABLEAU (JTABLE)

        // Structure globale des données du tableau
        String[] cols = new String[]{"ID_DB", "Date", "E-mail", "Sujet", "Statut", "Corps"};

        supportTableModel = new DefaultTableModel(new Object[0][6], cols) {
            @Override public boolean isCellEditable(int r, int c) { return false; } // Cellules non modifiables au double-clic
        };

        supportTable = new JTable(supportTableModel);
        supportTable.setRowHeight(35);
        supportTable.setGridColor(mainFrame.CLAIR_GRILLE);
        supportTable.setSelectionBackground(mainFrame.CYNA_BLEU);
        supportTable.setSelectionForeground(Color.WHITE);

        // Tri et filtrage par un TableRowSorter (opère sur le modèle complet)
        sorter = new TableRowSorter<>(supportTableModel);
        supportTable.setRowSorter(sorter);

        supportTable.removeColumn(supportTable.getColumnModel().getColumn(5)); // Cache "Corps"
        supportTable.removeColumn(supportTable.getColumnModel().getColumn(0)); // Cache "ID_DB"

        add(new JScrollPane(supportTable), BorderLayout.CENTER);

        // Premier chargement de la table au démarrage
        loadMessagesFromDB();

        // 3bis : RAFRAÎCHISSEMENT TEMPS RÉEL (toutes les 5 secondes)
        // On ne recharge que si la base a changé, pour préserver le tri et la
        // sélection de l'utilisateur tant qu'aucun nouveau message n'arrive.
        refreshTimer = new Timer(5000, e -> {
            if (databaseHasChanged()) {
                loadMessagesFromDB();
            }
        });
        refreshTimer.start();
    }

    // 3ter : APPLICATION DES FILTRES (statut + recherche texte)
    private void applyFilter() {
        List<RowFilter<Object, Object>> filters = new ArrayList<>();

        // Filtre par statut (colonne 4 du modèle : libellé UI)
        String status = (String) filterStatus.getSelectedItem();
        if (status != null && !"Tous".equals(status)) {
            filters.add(RowFilter.regexFilter("^" + Pattern.quote(status) + "$", 4));
        }

        // Filtre texte sur e-mail (2), sujet (3) et corps (5)
        String q = filterSearch.getText().trim();
        if (!q.isEmpty()) {
            filters.add(RowFilter.regexFilter("(?i)" + Pattern.quote(q), 2, 3, 5));
        }

        sorter.setRowFilter(filters.isEmpty() ? null : RowFilter.andFilter(filters));
    }

    // 4 : FENÊTRE DE DIALOGUE (DÉTAILS ET MODIFICATION DU STATUT)
    private void openSupportEditDialog(int viewRowIndex) {
        // Récupération sécurisée dans le modèle (permet de lire le "Corps" et l'"ID" bien qu'ils soient masqués à l'écran)
        int modelRowIndex = supportTable.convertRowIndexToModel(viewRowIndex);

        int idDb = (int) supportTableModel.getValueAt(modelRowIndex, 0);
        String email = supportTableModel.getValueAt(modelRowIndex, 2).toString();
        String sujet = supportTableModel.getValueAt(modelRowIndex, 3).toString();
        String statutUI = supportTableModel.getValueAt(modelRowIndex, 4).toString();
        String corps = supportTableModel.getValueAt(modelRowIndex, 5).toString();

        // Configuration de la Popup Modale
        JDialog dialog = new JDialog(mainFrame, "Détails du message", true);
        dialog.setSize(450, 420);
        dialog.setLocationRelativeTo(mainFrame);
        dialog.setLayout(new BorderLayout(10, 10));

        JPanel mainContainer = new JPanel();
        mainContainer.setLayout(new BoxLayout(mainContainer, BoxLayout.Y_AXIS));
        mainContainer.setBorder(new EmptyBorder(15, 15, 15, 15));

        // Formulaire d'information
        JPanel formPanel = new JPanel(new GridLayout(3, 2, 10, 10));
        formPanel.setOpaque(false);

        formPanel.add(new JLabel("E-mail :"));
        JTextField txtEmail = new JTextField(email);
        txtEmail.setEditable(false);
        formPanel.add(txtEmail);

        formPanel.add(new JLabel("Sujet :"));
        JTextField txtSubject = new JTextField(sujet);
        txtSubject.setEditable(false);
        formPanel.add(txtSubject);

        formPanel.add(new JLabel("Statut :"));
        String[] statusesUI = new String[]{"NOUVEAU", "LU", "ARCHIVÉ"};
        JComboBox<String> cbStatus = new JComboBox<>(statusesUI);
        cbStatus.setSelectedItem(statutUI);
        formPanel.add(cbStatus);

        mainContainer.add(formPanel);
        mainContainer.add(Box.createRigidArea(new Dimension(0, 15)));

        // Zone de lecture pour le contenu complet du message
        JLabel lblBody = new JLabel("Contenu du message :");
        lblBody.setFont(new Font("SansSerif", Font.BOLD, 12));
        lblBody.setAlignmentX(Component.LEFT_ALIGNMENT);
        mainContainer.add(lblBody);
        mainContainer.add(Box.createRigidArea(new Dimension(0, 5)));

        JTextArea areaCorps = new JTextArea(corps);
        areaCorps.setEditable(false);
        areaCorps.setLineWrap(true);
        areaCorps.setWrapStyleWord(true);
        areaCorps.setFont(new Font("SansSerif", Font.PLAIN, 12));

        JScrollPane scrollCorps = new JScrollPane(areaCorps);
        scrollCorps.setPreferredSize(new Dimension(400, 120));
        scrollCorps.setAlignmentX(Component.LEFT_ALIGNMENT);
        mainContainer.add(scrollCorps);
        mainContainer.add(Box.createRigidArea(new Dimension(0, 15)));

        // Enregistrement des modifications
        JButton btnSave = new JButton("Mettre à jour le statut");
        btnSave.setAlignmentX(Component.LEFT_ALIGNMENT);
        btnSave.setMaximumSize(new Dimension(Integer.MAX_VALUE, 35));
        btnSave.addActionListener(e -> {
            String newStatusUI = cbStatus.getSelectedItem().toString();
            String statusDB = mapUIToDBStatut(newStatusUI); // Conversion FR UI -> EN BDD

            updateMessageStatusInDB(idDb, statusDB);
            loadMessagesFromDB();
            dialog.dispose();
        });
        mainContainer.add(btnSave);

        dialog.add(mainContainer, BorderLayout.CENTER);

        // Dynamic Theme (Sombre/Clair)
        dialog.getContentPane().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : mainFrame.CLAIR_CONTENEUR);
        mainFrame.updateDarkModeRecursively(dialog.getContentPane(), mainFrame.isDarkModeActive);

        dialog.setVisible(true);
    }

    // 5 : REQUÊTES BASE DE DONNÉES (CRUD)

    private void loadMessagesFromDB() {
        // Mémorise la sélection courante (par ID) pour la restaurer après rechargement
        int selectedId = currentSelectedId();

        supportTableModel.setRowCount(0); // Clear du tableau avant insertion
        String query = "SELECT id, email, sujet, corps, statut, cree_le FROM messages_contact ORDER BY cree_le DESC";

        int count = 0;
        long maxId = 0;
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query);
             ResultSet rs = ps.executeQuery()) {

            while (rs.next()) {
                int id = rs.getInt("id");
                count++;
                if (id > maxId) maxId = id;

                // Troncature des secondes sur la date (Format : AAAA-MM-JJ HH:MM)
                String rawDate = rs.getString("cree_le");
                String dateAffichee = rawDate != null && rawDate.length() > 16 ? rawDate.substring(0, 16) : rawDate;

                String email = rs.getString("email");
                String sujet = rs.getString("sujet");
                String corps = rs.getString("corps");

                // Conversion de l'ENUM SQL (Anglais) vers le libellé UI (Français)
                String statutUI = mapDBToUIStatut(rs.getString("statut"));

                // Insertion stricte selon l'ordre du tableau de colonnes
                supportTableModel.addRow(new Object[]{id, dateAffichee, email, sujet, statutUI, corps});
            }
        } catch (Exception e) {
            e.printStackTrace();
        }

        // Mémorise l'état pour la détection de changement (temps réel)
        lastKnownCount = count;
        lastKnownMaxId = maxId;

        applyFilter();            // Ré-applique le filtre courant après rechargement
        restoreSelection(selectedId);
    }

    /**
     * Détecte si la table messages_contact a changé depuis le dernier chargement
     * (nouveau message, suppression, etc.) sans recharger toutes les lignes.
     */
    private boolean databaseHasChanged() {
        String query = "SELECT COUNT(*) AS c, COALESCE(MAX(id), 0) AS m FROM messages_contact";
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query);
             ResultSet rs = ps.executeQuery()) {
            if (rs.next()) {
                int count = rs.getInt("c");
                long maxId = rs.getLong("m");
                return count != lastKnownCount || maxId != lastKnownMaxId;
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
        return false;
    }

    /** ID (BDD) de la ligne actuellement sélectionnée, ou -1. */
    private int currentSelectedId() {
        int viewRow = supportTable.getSelectedRow();
        if (viewRow == -1) return -1;
        try {
            int modelRow = supportTable.convertRowIndexToModel(viewRow);
            return (int) supportTableModel.getValueAt(modelRow, 0);
        } catch (Exception e) {
            return -1;
        }
    }

    /** Restaure la sélection sur la ligne portant l'ID donné, si elle existe encore. */
    private void restoreSelection(int id) {
        if (id < 0) return;
        for (int modelRow = 0; modelRow < supportTableModel.getRowCount(); modelRow++) {
            if ((int) supportTableModel.getValueAt(modelRow, 0) == id) {
                try {
                    int viewRow = supportTable.convertRowIndexToView(modelRow);
                    if (viewRow != -1) supportTable.setRowSelectionInterval(viewRow, viewRow);
                } catch (Exception ignored) { }
                return;
            }
        }
    }

    /**
     * Met à jour la colonne statut en BDD.
     */
    private void updateMessageStatusInDB(int id, String statutDB) {
        String query = "UPDATE messages_contact SET statut = ? WHERE id = ?";
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setString(1, statutDB);
            ps.setInt(2, id);
            ps.executeUpdate();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    /**
     * Supprime définitivement une ligne de la base.
     */
    private void deleteMessageFromDB(int id) {
        String query = "DELETE FROM messages_contact WHERE id = ?";
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    // 6 : UTILITAIRES DE MAPPAGE DE LANGUE (BDD EN <-> INTERFACE FR)

    /**
     * Reçoit le statut brut de la base de données et le traduit pour l'affichage utilisateur.
     */
    private String mapDBToUIStatut(String dbStatut) {
        if (dbStatut == null) return "NOUVEAU";
        switch (dbStatut.toLowerCase()) {
            case "read": return "LU";
            case "archived": return "ARCHIVÉ";
            case "new":
            default: return "NOUVEAU";
        }
    }

    /**
     * Reçoit le choix français de l'utilisateur et renvoie la valeur attendue par l'ENUM de la base MySQL.
     */
    private String mapUIToDBStatut(String uiStatut) {
        if (uiStatut.equals("LU")) return "read";
        if (uiStatut.equals("ARCHIVÉ")) return "archived";
        return "new";
    }
}
