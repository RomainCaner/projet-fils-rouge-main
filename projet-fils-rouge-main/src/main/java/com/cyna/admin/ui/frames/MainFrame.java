package com.cyna.admin.ui.frames;

import org.jfree.chart.ChartPanel;
import org.jfree.chart.JFreeChart;

import com.cyna.admin.database.DBConnection; 
import com.cyna.admin.ui.panels.ContentPanel;
import com.cyna.admin.ui.panels.DashboardPanel;
import com.cyna.admin.ui.panels.InstallationsPanel;
import com.cyna.admin.ui.panels.OrdersPanel;
import com.cyna.admin.ui.panels.ProductsPanel;
import com.cyna.admin.ui.panels.SettingsPanel;
import com.cyna.admin.ui.panels.SubscriptionsPanel;
import com.cyna.admin.ui.panels.SupportPanel;
import com.cyna.admin.ui.utils.TableRowTransferHandler;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.WindowAdapter; 
import java.awt.event.WindowEvent;   
import java.sql.Connection;          
import java.sql.PreparedStatement;   
import java.sql.ResultSet;           

public class MainFrame extends JFrame {

    private static final long serialVersionUID = 1L;
    
    // PARTIE 1 : ATTRIBUTS DE STRUCTURE ET PALETTE DE COULEURS CONSTANTES
    private JPanel cardsPanel;
    private CardLayout cardLayout;
    private JPanel topBar;
    private JLabel topBarTitle;
    
    // Références globales vers les contrôleurs de panneaux spécialisés
    public SettingsPanel settingsPanel;
    private InstallationsPanel installationsPanel;
    public JButton btnSettings;

    // Palette Thème Clair
    public final Color CLAIR_FOND_PRINCIPAL = new Color(248, 250, 252);
    public final Color CLAIR_CONTENEUR = Color.WHITE;
    public final Color CLAIR_TEXTE = Color.BLACK;
    public final Color CLAIR_GRILLE = new Color(230, 230, 230);
    
    // Couleurs d'identité visuelle (Charte CYNA)
    public final Color CYNA_BLEU = new Color(37, 99, 235);
    public final Color CYNA_VERT = new Color(16, 185, 129);
    public final Color CYNA_DARK = new Color(15, 23, 42);

    // Palette Thème Sombre
    public final Color SOMBRE_FOND_PRINCIPAL = new Color(17, 24, 39); 
    public final Color SOMBRE_CONTENEUR = new Color(31, 41, 55);    
    public final Color SOMBRE_TEXTE = new Color(209, 213, 219);      
    public final Color SOMBRE_GRILLE = new Color(55, 65, 81);        

    public boolean isDarkModeActive = false;

    // PARTIE 2 : CONSTRUCTEUR ET HOOKS DE CYCLE DE VIE DE LA FENÊTRE
    public MainFrame() {
        // Chargement à la racine de la configuration utilisateur persistante
        loadThemeFromDB();

        setTitle("CYNA - Console d'Administration Système");
        setSize(1400, 900);
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        
        // Interception du signal de fermeture pour l'enregistrement automatique du panneau de configuration
        addWindowListener(new WindowAdapter() {
            @Override
            public void windowClosing(WindowEvent e) {
                if (settingsPanel != null) {
                    settingsPanel.autoSaveIfEnabled();
                }
            }
        });

        setLocationRelativeTo(null);
        setLayout(new BorderLayout());
        
        java.net.URL iconURL = getClass().getResource("/logo.png");
        if (iconURL != null) setIconImage(new ImageIcon(iconURL).getImage());

        // PARTIE 3 : ARCHITECTURE DE LA BARRE LATÉRALE DE NAVIGATION (SIDEBAR)
        JPanel sidebar = new JPanel();
        sidebar.setBackground(CYNA_DARK); 
        sidebar.setPreferredSize(new Dimension(260, 0));
        sidebar.setLayout(new BoxLayout(sidebar, BoxLayout.Y_AXIS));
        sidebar.setBorder(new EmptyBorder(20, 15, 20, 15));

        JLabel sideLogo = new JLabel("CYNA ADMIN");
        sideLogo.setForeground(Color.WHITE);
        sideLogo.setFont(new Font("SansSerif", Font.BOLD, 22));
        sidebar.add(sideLogo);
        sidebar.add(Box.createRigidArea(new Dimension(0, 40)));

        cardLayout = new CardLayout();
        cardsPanel = new JPanel(cardLayout);

        // Initialisation et allocation des contrôleurs d'onglets principaux
        installationsPanel = new InstallationsPanel(this);
        settingsPanel = new SettingsPanel(this);

        cardsPanel.add(new DashboardPanel(this), "Dashboard");
        cardsPanel.add(new ProductsPanel(this), "Products");
        cardsPanel.add(new OrdersPanel(this), "Orders");
        cardsPanel.add(new SubscriptionsPanel(this), "Subscriptions");
        cardsPanel.add(installationsPanel, "Installations");
        cardsPanel.add(new SupportPanel(this), "Support"); 
        cardsPanel.add(new ContentPanel(this), "Content");
        cardsPanel.add(settingsPanel, "Settings");

        // Injection des boutons de routage et des sous-titres d'en-tête associés
        sidebar.add(createMenuButton("Tableau de bord", "Dashboard", "Tableau de Bord & KPIs"));
        sidebar.add(Box.createRigidArea(new Dimension(0, 10)));

        sidebar.add(createMenuButton("Services SaaS", "Products", "Inventaire des Services SaaS"));
        sidebar.add(Box.createRigidArea(new Dimension(0, 10)));

        sidebar.add(createMenuButton("Commandes Clients", "Orders", "Gestion des Commandes Clients"));
        sidebar.add(Box.createRigidArea(new Dimension(0, 10)));

        sidebar.add(createMenuButton("Abonnements", "Subscriptions", "Gestion des Abonnements"));
        sidebar.add(Box.createRigidArea(new Dimension(0, 10)));

        sidebar.add(createMenuButton("Déploiements", "Installations", "Suivi des Installations"));
        sidebar.add(Box.createRigidArea(new Dimension(0, 10)));

        sidebar.add(createMenuButton("Support & Messages", "Support", "Support - Formulaire de contact"));
        sidebar.add(Box.createRigidArea(new Dimension(0, 10)));

        sidebar.add(createMenuButton("Contenu Site", "Content", "Gestion du Carrousel & Textes Front-end"));
        sidebar.add(Box.createRigidArea(new Dimension(0, 10)));

        btnSettings = createMenuButton("Configuration", "Settings", "Configuration Système");
        sidebar.add(btnSettings);

        sidebar.add(Box.createVerticalGlue()); 
        
        JButton logoutBtn = new JButton("Déconnexion");
        logoutBtn.setForeground(new Color(239, 68, 68));
        logoutBtn.setContentAreaFilled(false);
        logoutBtn.setBorderPainted(false);
        logoutBtn.setCursor(new Cursor(Cursor.HAND_CURSOR));
        logoutBtn.addActionListener(e -> {
            new LoginFrame().setVisible(true);
            this.dispose();
        });
        sidebar.add(logoutBtn);

        // Configuration de la barre supérieure d'en-tête (TopBar)
        topBar = new JPanel(new BorderLayout());
        topBar.setBackground(CLAIR_CONTENEUR);
        topBar.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, CLAIR_GRILLE),
            new EmptyBorder(15, 30, 15, 30)
        ));

        topBarTitle = new JLabel("Tableau de Bord & KPIs");
        topBarTitle.setFont(new Font("SansSerif", Font.BOLD, 20));
        topBarTitle.setForeground(CLAIR_TEXTE);
        topBar.add(topBarTitle, BorderLayout.WEST);

        JPanel mainContent = new JPanel(new BorderLayout());
        mainContent.add(topBar, BorderLayout.NORTH);
        mainContent.add(cardsPanel, BorderLayout.CENTER);

        add(sidebar, BorderLayout.WEST);
        add(mainContent, BorderLayout.CENTER);

        // Application et propagation du filtre graphique
        applyDarkMode(this.isDarkModeActive);
    }

    // PARTIE 4 : FACTORY ET ÉCOUTEURS DES BOUTONS DE NAVIGATION INTERNE
    private JButton createMenuButton(String title, String cardName, String topTitle) {
        JButton btn = new JButton(title);
        btn.setForeground(new Color(200, 200, 200));
        btn.setContentAreaFilled(false);
        btn.setBorderPainted(false);
        btn.setHorizontalAlignment(SwingConstants.LEFT);
        btn.setMaximumSize(new Dimension(240, 40));
        btn.setFont(new Font("SansSerif", Font.PLAIN, 14));
        btn.setCursor(new Cursor(Cursor.HAND_CURSOR));
        btn.addActionListener(e -> {
            cardLayout.show(cardsPanel, cardName);
            topBarTitle.setText(topTitle);
        });
        return btn;
    }

    public InstallationsPanel getInstallationsPanel() {
        return installationsPanel;
    }

    // PARTIE 5 : BOÎTES DE DIALOGUE MODALES STYLISÉES (CONFIRMATION / MESSAGE)
    public int showCustomConfirmDialog(String message, String title) {
        JOptionPane pane = new JOptionPane(message, JOptionPane.QUESTION_MESSAGE, JOptionPane.YES_NO_OPTION);
        pane.setOpaque(true);
        pane.setBackground(isDarkModeActive ? SOMBRE_CONTENEUR : CLAIR_CONTENEUR);
        JDialog dialog = pane.createDialog(this, title);
        dialog.getContentPane().setBackground(isDarkModeActive ? SOMBRE_CONTENEUR : CLAIR_CONTENEUR);
        updateDarkModeRecursively(pane, isDarkModeActive);
        dialog.setVisible(true);
        Object selectedValue = pane.getValue();
        if (selectedValue == null) return JOptionPane.CLOSED_OPTION;
        if (selectedValue instanceof Integer) return (Integer) selectedValue;
        return JOptionPane.CLOSED_OPTION;
    }

    public void showCustomMessageDialog(String message) {
        JOptionPane pane = new JOptionPane(message, JOptionPane.INFORMATION_MESSAGE);
        pane.setOpaque(true);
        pane.setBackground(isDarkModeActive ? SOMBRE_CONTENEUR : CLAIR_CONTENEUR);
        JDialog dialog = pane.createDialog(this, "Information");
        dialog.getContentPane().setBackground(isDarkModeActive ? SOMBRE_CONTENEUR : CLAIR_CONTENEUR);
        updateDarkModeRecursively(pane, isDarkModeActive);
        dialog.setVisible(true);
    }
    
    // PARTIE 6 : CONFIGURATEUR ET COMPOSANTS DE CELLULES DES GRILLES JTABLE
    public void configureTable(JTable table, int statusColumnIndex, String[] statusItems) {
        table.setDragEnabled(true);
        table.setDropMode(DropMode.INSERT_ROWS);
        table.setTransferHandler(new TableRowTransferHandler(table));

        JComboBox<String> comboBox = new JComboBox<>(statusItems);
        comboBox.setBackground(SOMBRE_CONTENEUR); 
        comboBox.setForeground(Color.WHITE);      
        comboBox.setOpaque(true);

        comboBox.setRenderer(new DefaultListCellRenderer() {
            @Override
            public java.awt.Component getListCellRendererComponent(JList<?> list, Object value, int index, boolean isSelected, boolean cellHasFocus) {
                java.awt.Component c = super.getListCellRendererComponent(list, value, index, isSelected, cellHasFocus);
                c.setBackground(isSelected ? CYNA_BLEU : SOMBRE_CONTENEUR);
                c.setForeground(Color.WHITE);
                return c;
            }
        });

        table.getColumnModel().getColumn(statusColumnIndex).setCellEditor(new DefaultCellEditor(comboBox));
    }

    // PARTIE 7 : MOTEUR DE PERSISTANCE ET RENDU DYNAMIQUE DES THÈMES (DARK/LIGHT)
    private void loadThemeFromDB() {
        String query = "SELECT valeur FROM reglages WHERE cle = 'dark_mode'";
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query);
             ResultSet rs = ps.executeQuery()) {
             
            if (rs.next()) {
                this.isDarkModeActive = "1".equals(rs.getString("valeur"));
            }
        } catch (Exception e) {
            System.out.println("Erreur au chargement du thème depuis MainFrame : " + e.getMessage());
        }
    }

    public void applyDarkMode(boolean isDark) {
        this.isDarkModeActive = isDark; 
        topBar.setBackground(isDark ? SOMBRE_CONTENEUR : CLAIR_CONTENEUR);
        topBar.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, isDark ? SOMBRE_GRILLE : CLAIR_GRILLE),
            new EmptyBorder(15, 30, 15, 30)
        ));
        topBarTitle.setForeground(isDark ? SOMBRE_TEXTE : CLAIR_TEXTE);
        updateDarkModeRecursively(cardsPanel, isDark);
        cardsPanel.repaint();
    }

    public void updateDarkModeRecursively(Container container, boolean isDark) {
        Color bgPrincipal = isDark ? SOMBRE_FOND_PRINCIPAL : CLAIR_FOND_PRINCIPAL;
        Color bgConteneur = isDark ? SOMBRE_CONTENEUR : CLAIR_CONTENEUR;
        Color fgTexte = isDark ? SOMBRE_TEXTE : CLAIR_TEXTE;
        Color grille = isDark ? SOMBRE_GRILLE : CLAIR_GRILLE;

        for (Component c : container.getComponents()) {
            if (c instanceof ChartPanel) {
                JFreeChart chart = ((ChartPanel) c).getChart();
                if (isDark) styleChartDark(chart); else styleChartLight(chart);
                continue; 
            }
            if (c instanceof JPanel) {
                JPanel panel = (JPanel) c;
                panel.setOpaque(true);
                if (panel.getParent() != null && panel.getParent().equals(cardsPanel)) panel.setBackground(bgPrincipal);
                else panel.setBackground(bgConteneur);
                updateDarkModeRecursively(panel, isDark);
            } else if (c instanceof JScrollPane) {
                c.setBackground(bgConteneur);
                ((JScrollPane) c).getViewport().setBackground(bgConteneur);
                updateDarkModeRecursively((Container) c, isDark);
            } else if (c instanceof JViewport) {
                c.setBackground(bgConteneur);
                updateDarkModeRecursively((Container) c, isDark);
            } else if (c instanceof JTable) {
                JTable table = (JTable) c;
                table.setBackground(bgConteneur); 
                table.setForeground(fgTexte); 
                table.setGridColor(grille);
                table.getTableHeader().setBackground(bgPrincipal); 
                table.getTableHeader().setForeground(fgTexte);
                table.setSelectionBackground(CYNA_BLEU);
                table.setSelectionForeground(Color.WHITE);
            } else if (c instanceof JLabel || c instanceof JCheckBox) {
                c.setForeground(fgTexte);
            } else if (c instanceof JTextField || c instanceof JTextArea || c instanceof JSpinner) {
                c.setBackground(isDark ? SOMBRE_FOND_PRINCIPAL : Color.WHITE);
                c.setForeground(fgTexte);
            } else if (c instanceof JButton) {
                JButton btn = (JButton) c;
                btn.setBackground(isDark ? SOMBRE_CONTENEUR : CLAIR_CONTENEUR);
                if (!Color.RED.equals(btn.getForeground())) btn.setForeground(fgTexte);
            } else if (c instanceof JComboBox) {
                JComboBox<?> cb = (JComboBox<?>) c;
                cb.setBackground(isDark ? SOMBRE_FOND_PRINCIPAL : Color.WHITE);
                cb.setForeground(fgTexte);
                cb.setRenderer(new DefaultListCellRenderer() {
                    @Override
                    public Component getListCellRendererComponent(JList<?> list, Object value, int index, boolean isSelected, boolean cellHasFocus) {
                        Component comp = super.getListCellRendererComponent(list, value, index, isSelected, cellHasFocus);
                        comp.setBackground(isSelected ? CYNA_BLEU : (isDark ? SOMBRE_FOND_PRINCIPAL : Color.WHITE));
                        comp.setForeground(isSelected ? Color.WHITE : (isDark ? SOMBRE_TEXTE : CLAIR_TEXTE));
                        return comp;
                    }
                });
            } else if (c instanceof JList) {
                c.setBackground(bgConteneur); 
                c.setForeground(fgTexte);
                ((JList<?>) c).setSelectionBackground(CYNA_BLEU);
                ((JList<?>) c).setSelectionForeground(Color.WHITE);
            }
        }
    }

    public void styleChartLight(JFreeChart chart) {
        chart.setBackgroundPaint(CLAIR_CONTENEUR);
        chart.setBorderVisible(false);
        chart.getTitle().setPaint(CLAIR_TEXTE);
        if (chart.getLegend() != null) {
            chart.getLegend().setBackgroundPaint(CLAIR_CONTENEUR);
            chart.getLegend().setItemPaint(CLAIR_TEXTE);
        }
        org.jfree.chart.plot.Plot plot = chart.getPlot();
        if (plot instanceof org.jfree.chart.plot.CategoryPlot) {
            org.jfree.chart.plot.CategoryPlot catPlot = (org.jfree.chart.plot.CategoryPlot) plot;
            catPlot.setBackgroundPaint(CLAIR_CONTENEUR);
            catPlot.setOutlineVisible(false);
            catPlot.setRangeGridlinePaint(CLAIR_GRILLE); 
            org.jfree.chart.renderer.category.BarRenderer renderer = (org.jfree.chart.renderer.category.BarRenderer) catPlot.getRenderer();
            renderer.setBarPainter(new org.jfree.chart.renderer.category.StandardBarPainter()); 
        } else if (plot instanceof org.jfree.chart.plot.PiePlot) {
            org.jfree.chart.plot.PiePlot<?> piePlot = (org.jfree.chart.plot.PiePlot<?>) plot;
            piePlot.setBackgroundPaint(CLAIR_CONTENEUR);
            piePlot.setLabelBackgroundPaint(Color.WHITE);
            piePlot.setLabelPaint(CLAIR_TEXTE);
            piePlot.setLabelOutlinePaint(CLAIR_GRILLE);
            piePlot.setShadowPaint(null);
            piePlot.setOutlineVisible(false);
        }
    }

    public void styleChartDark(JFreeChart chart) {
        chart.setBackgroundPaint(SOMBRE_CONTENEUR);
        chart.setBorderVisible(false);
        chart.getTitle().setPaint(SOMBRE_TEXTE);
        if (chart.getLegend() != null) {
            chart.getLegend().setBackgroundPaint(SOMBRE_CONTENEUR);
            chart.getLegend().setItemPaint(SOMBRE_TEXTE);
        }
        org.jfree.chart.plot.Plot plot = chart.getPlot();
        if (plot instanceof org.jfree.chart.plot.CategoryPlot) {
            org.jfree.chart.plot.CategoryPlot catPlot = (org.jfree.chart.plot.CategoryPlot) plot;
            catPlot.setBackgroundPaint(SOMBRE_CONTENEUR);
            catPlot.setOutlineVisible(false);
            catPlot.setRangeGridlinePaint(SOMBRE_GRILLE); 
        } else if (plot instanceof org.jfree.chart.plot.PiePlot) {
            org.jfree.chart.plot.PiePlot<?> piePlot = (org.jfree.chart.plot.PiePlot<?>) plot;
            piePlot.setBackgroundPaint(SOMBRE_CONTENEUR);
            piePlot.setLabelBackgroundPaint(SOMBRE_FOND_PRINCIPAL);
            piePlot.setLabelPaint(SOMBRE_TEXTE);
            piePlot.setLabelOutlinePaint(SOMBRE_GRILLE);           
            piePlot.setShadowPaint(null);
            piePlot.setOutlineVisible(false);
        }
    }
}