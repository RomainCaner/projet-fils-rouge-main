package com.cyna.admin.ui.panels;

import org.jfree.chart.ChartFactory;
import org.jfree.chart.ChartPanel;
import org.jfree.chart.JFreeChart;
import org.jfree.chart.plot.PlotOrientation;
import org.jfree.data.category.DefaultCategoryDataset;
import org.jfree.data.general.DefaultPieDataset;

import com.cyna.admin.database.DBConnection;
import com.cyna.admin.ui.frames.MainFrame;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.text.SimpleDateFormat;

public class DashboardPanel extends JPanel {

    // 1 : ATTRIBUTS ET CONFIGURATION DU PANNEAU
    private MainFrame mainFrame;
    private DefaultCategoryDataset salesData;
    private DefaultCategoryDataset revenueData;
    private DefaultPieDataset<String> pieData;
    private JFreeChart salesChart, revenueChart, pieChart;

    public DashboardPanel(MainFrame mainFrame) {
        this.mainFrame = mainFrame;
        setLayout(new BorderLayout());
        setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
        setBorder(new EmptyBorder(20, 20, 20, 20));

        // 2 : BARRE DE FILTRAGE TEMPOREL ET ACTIONS UI
        JPanel filterPanel = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        filterPanel.setOpaque(false);
        
        String[] filterOptions = new String[]{"7 Derniers Jours", "5 Dernières Semaines", "Personnalisé..."};
            
        JComboBox<String> timeFilter = new JComboBox<>(filterOptions);
        filterPanel.add(new JLabel("Période d'analyse : "));
        filterPanel.add(timeFilter);
        add(filterPanel, BorderLayout.NORTH);

        // 3 : CONSTITUTION DE LA GRILLE ET DES GRAPHIKES JFREECHART
        JPanel chartsPanel = new JPanel(new GridLayout(2, 2, 20, 20));
        chartsPanel.setOpaque(false);

        salesData = new DefaultCategoryDataset();
        revenueData = new DefaultCategoryDataset();
        pieData = new DefaultPieDataset<>();

        // 1. Histogramme classique : Évolution des Ventes
        salesChart = ChartFactory.createBarChart(
            "Évolution des Ventes", "", "Revenus (€)", 
            salesData, PlotOrientation.VERTICAL, false, true, false);
            
        // 2. Histogramme empilé : Revenus par Secteur d'activité
        revenueChart = ChartFactory.createStackedBarChart(
            "Revenus par Secteur", "", "Revenus (€)", 
            revenueData, PlotOrientation.VERTICAL, true, true, false);
            
        // 3. Diagramme circulaire : Répartition globale
        pieChart = ChartFactory.createPieChart(
            "Répartition des Ventes", pieData, true, true, false);

        // Style initial (Thème Clair par défaut)
        mainFrame.styleChartLight(salesChart);
        salesChart.getCategoryPlot().getRenderer().setSeriesPaint(0, mainFrame.CYNA_BLEU);
        
        mainFrame.styleChartLight(revenueChart);
        mainFrame.styleChartLight(pieChart);

        // Intégration des conteneurs Swing JFreeChart à l'affichage
        chartsPanel.add(new ChartPanel(salesChart));
        chartsPanel.add(new ChartPanel(revenueChart));
        chartsPanel.add(new ChartPanel(pieChart));

        // Chargement initial des données sur la période standard (7 jours)
        updateAllCharts(7);

        // Écouteur sur la boîte de sélection pour recalculer les statistiques
        timeFilter.addActionListener(e -> {
            int selected = timeFilter.getSelectedIndex();
            if (selected == 0) updateAllCharts(7);
            else if (selected == 1) updateAllCharts(35);
            else if (selected == 2) openCustomTimeFilterDialog(timeFilter);
        });

        add(chartsPanel, BorderLayout.CENTER);
    }

    // 4 : FENÊTRE DE DIALOGUE INTERNE (FILTRE SUR-MESURE)
    private void openCustomTimeFilterDialog(JComboBox<String> timeFilter) {
        JDialog dialog = new JDialog(mainFrame, "Période Personnalisée", true);
        dialog.setSize(350, 180);
        dialog.setLocationRelativeTo(mainFrame);
        dialog.setLayout(new BorderLayout(10, 10));

        JPanel contentPanel = new JPanel(new GridLayout(2, 1, 10, 10));
        contentPanel.setBorder(new EmptyBorder(15, 15, 5, 15));
        contentPanel.setOpaque(false);
        contentPanel.add(new JLabel("Choisissez la durée (entre 7 j et 5 sem) :", SwingConstants.CENTER));

        JPanel inputPanel = new JPanel(new FlowLayout(FlowLayout.CENTER));
        inputPanel.setOpaque(false);
        JSpinner numSpinner = new JSpinner(new SpinnerNumberModel(7, 1, 35, 1));
        
        String[] units = new String[]{"Jours", "Semaines"};
        JComboBox<String> unitBox = new JComboBox<>(units);
        
        inputPanel.add(numSpinner);
        inputPanel.add(unitBox);
        contentPanel.add(inputPanel);

        JButton btnValider = new JButton("Valider");
        btnValider.addActionListener(e -> {
            int val = (int) numSpinner.getValue();
            String unit = (String) unitBox.getSelectedItem();
            int days = "Semaines".equals(unit) ? val * 7 : val;

            // Seuil de sécurité métier (min 7 jours, max 35 jours)
            if (days >= 7 && days <= 35) {
                updateAllCharts(days);
                dialog.dispose();
            } else {
                mainFrame.showCustomMessageDialog("La période doit être comprise entre 7 jours et 5 semaines (35 jours).");
            }
        });

        JPanel btnPanel = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        btnPanel.setOpaque(false);
        btnPanel.setBorder(new EmptyBorder(0, 15, 15, 15));
        btnPanel.add(btnValider);

        dialog.add(contentPanel, BorderLayout.CENTER);
        dialog.add(btnPanel, BorderLayout.SOUTH);

        // Sécurité : si la boîte modale est fermée brusquement, on réinitialise l'affichage sur les 7 jours
        dialog.addWindowListener(new WindowAdapter() {
            @Override 
            public void windowClosing(WindowEvent windowEvent) { timeFilter.setSelectedIndex(0); }
        });

        dialog.getContentPane().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : mainFrame.CLAIR_CONTENEUR);
        mainFrame.updateDarkModeRecursively(dialog.getContentPane(), mainFrame.isDarkModeActive);
        dialog.setVisible(true);
    }

    // 5 : ACCÈS BDD & INJECTEUR DE DONNÉES (DATASETS)
    private void updateAllCharts(int days) {
        // Purge intégrale des anciens rendus avant insertion des nouvelles coordonnées
        salesData.clear(); 
        revenueData.clear(); 
        pieData.clear();

        String strSales = "Ventes";
        SimpleDateFormat dateFormat = new SimpleDateFormat("dd/MM");

        try (Connection conn = DBConnection.getConnection()) {
            
            // Requete 1 : Volume financier global cumulé par tranche de 24h (Uniquement les commandes payées)
            String sqlSales = "SELECT DATE(cree_le) as date_cmd, SUM(total_centimes) as total " +
                              "FROM commandes " +
                              "WHERE statut IN ('paid', 'active', 'renewed') " +
                              "AND cree_le >= DATE_SUB(CURDATE(), INTERVAL ? DAY) " +
                              "GROUP BY DATE(cree_le) ORDER BY date_cmd ASC";
            
            try (PreparedStatement stmt = conn.prepareStatement(sqlSales)) {
                stmt.setInt(1, days);
                try (ResultSet rs = stmt.executeQuery()) {
                    while (rs.next()) {
                        String date = dateFormat.format(rs.getDate("date_cmd"));
                        double revenueEuros = rs.getInt("total") / 100.0;
                        salesData.addValue(revenueEuros, strSales, date);
                    }
                }
            }

            // Requete 2 : Ventilation financière par types/catégories de produits au fil du temps (Graphique empilé)
            String sqlRevenue = "SELECT DATE(cmd.cree_le) as date_cmd, c.nom as categorie, SUM(lc.total_ligne_centimes) as total " +
                                "FROM lignes_commande lc " +
                                "JOIN commandes cmd ON lc.commande_id = cmd.id " +
                                "JOIN produits p ON lc.produit_id = p.id " +
                                "JOIN categories c ON p.categorie_id = c.id " +
                                "WHERE cmd.statut IN ('paid', 'active', 'renewed') " +
                                "AND cmd.cree_le >= DATE_SUB(CURDATE(), INTERVAL ? DAY) " +
                                "GROUP BY DATE(cmd.cree_le), c.id ORDER BY date_cmd ASC";

            try (PreparedStatement stmt = conn.prepareStatement(sqlRevenue)) {
                stmt.setInt(1, days);
                try (ResultSet rs = stmt.executeQuery()) {
                    while (rs.next()) {
                        String date = dateFormat.format(rs.getDate("date_cmd"));
                        String category = rs.getString("categorie");
                        double revenueEuros = rs.getInt("total") / 100.0;
                        revenueData.addValue(revenueEuros, category, date);
                    }
                }
            }

            // Requete 3 : Répartition macroscopique des parts de marché internes (Camembert)
            String sqlPie = "SELECT c.nom as categorie, SUM(lc.total_ligne_centimes) as total " +
                            "FROM lignes_commande lc " +
                            "JOIN commandes cmd ON lc.commande_id = cmd.id " +
                            "JOIN produits p ON lc.produit_id = p.id " +
                            "JOIN categories c ON p.categorie_id = c.id " +
                            "WHERE cmd.statut IN ('paid', 'active', 'renewed') " +
                            "AND cmd.cree_le >= DATE_SUB(CURDATE(), INTERVAL ? DAY) " +
                            "GROUP BY c.id";

            try (PreparedStatement stmt = conn.prepareStatement(sqlPie)) {
                stmt.setInt(1, days);
                try (ResultSet rs = stmt.executeQuery()) {
                    while (rs.next()) {
                        String category = rs.getString("categorie");
                        double revenueEuros = rs.getInt("total") / 100.0;
                        pieData.setValue(category, revenueEuros);
                    }
                }
            }

        } catch (Exception e) {
            e.printStackTrace();
            System.err.println("Erreur lors de la récupération des données : " + e.getMessage());
        }

        // Mutation dynamique des couleurs de fond et de police des graphiques JFreeChart
        if (mainFrame.isDarkModeActive) {
            mainFrame.styleChartDark(salesChart); 
            mainFrame.styleChartDark(revenueChart); 
            mainFrame.styleChartDark(pieChart);
        } else {
            mainFrame.styleChartLight(salesChart); 
            mainFrame.styleChartLight(revenueChart); 
            mainFrame.styleChartLight(pieChart);
        }
    }
}