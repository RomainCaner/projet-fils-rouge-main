package com.cyna.admin;

import javax.swing.SwingUtilities;

import com.cyna.admin.ui.frames.LoginFrame;
import com.formdev.flatlaf.FlatLightLaf;

public class Main {
    public static void main(String[] args) {
        // 1. On applique le look FlatLaf au démarrage
        try {
            FlatLightLaf.setup();
        } catch (Exception e) {
            e.printStackTrace();
        }

        // 2. On lance l'application proprement
        SwingUtilities.invokeLater(() -> {
            new LoginFrame().setVisible(true); 
        });
    }
}