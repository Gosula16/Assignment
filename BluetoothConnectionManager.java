package com.automotive.bluetooth;

import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.BluetoothProfile;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.util.Log;

import java.util.HashSet;
import java.util.Set;
import java.util.concurrent.ConcurrentHashMap;

/**
 * Manages Bluetooth connections in automotive systems to prevent duplicate device connections
 * and manage discovery list properly.
 */
public class BluetoothConnectionManager {
    private static final String TAG = "BluetoothConnectionManager";
    
    private Context context;
    private BluetoothAdapter bluetoothAdapter;
    
    // Store connected device MAC addresses to prevent duplicates
    private Set<String> connectedDeviceMacAddresses = ConcurrentHashMap.newKeySet();
    
    // Store device connection states
    private ConcurrentHashMap<String, Integer> deviceConnectionStates = new ConcurrentHashMap<>();
    
    // Callback interface for connection events
    public interface ConnectionCallback {
        void onDeviceConnected(BluetoothDevice device);
        void onDeviceDisconnected(BluetoothDevice device);
        void onConnectionBlocked(BluetoothDevice device, String reason);
        void onDiscoveryFiltered(BluetoothDevice device);
    }
    
    private ConnectionCallback connectionCallback;
    
    public BluetoothConnectionManager(Context context, ConnectionCallback callback) {
        this.context = context;
        this.connectionCallback = callback;
        this.bluetoothAdapter = BluetoothAdapter.getDefaultAdapter();
        
        // Register broadcast receivers for Bluetooth events
        registerBluetoothReceivers();
        
        // Initialize with currently connected devices
        initializeConnectedDevices();
    }
    
    /**
     * Register broadcast receivers to monitor Bluetooth connection events
     */
    private void registerBluetoothReceivers() {
        IntentFilter filter = new IntentFilter();
        filter.addAction(BluetoothDevice.ACTION_FOUND);
        filter.addAction(BluetoothAdapter.ACTION_DISCOVERY_FINISHED);
        filter.addAction(BluetoothDevice.ACTION_BOND_STATE_CHANGED);
        filter.addAction(BluetoothDevice.ACTION_ACL_CONNECTED);
        filter.addAction(BluetoothDevice.ACTION_ACL_DISCONNECTED);
        
        context.registerReceiver(bluetoothReceiver, filter);
    }
    
    /**
     * Initialize the manager with currently connected devices
     */
    private void initializeConnectedDevices() {
        if (bluetoothAdapter != null && bluetoothAdapter.isEnabled()) {
            Set<BluetoothDevice> bondedDevices = bluetoothAdapter.getBondedDevices();
            for (BluetoothDevice device : bondedDevices) {
                if (isDeviceConnected(device)) {
                    connectedDeviceMacAddresses.add(device.getAddress());
                    deviceConnectionStates.put(device.getAddress(), BluetoothProfile.STATE_CONNECTED);
                    Log.d(TAG, "Initialized connected device: " + device.getAddress());
                }
            }
        }
    }
    
    /**
     * Check if a device is currently connected
     */
    private boolean isDeviceConnected(BluetoothDevice device) {
        // Check various Bluetooth profiles to determine if device is connected
        // This is a simplified check - in real implementation, you'd check specific profiles
        // like A2DP, HFP, etc.
        return deviceConnectionStates.getOrDefault(device.getAddress(), BluetoothProfile.STATE_DISCONNECTED) 
               == BluetoothProfile.STATE_CONNECTED;
    }
    
    /**
     * Filter discovered devices to exclude already connected ones
     */
    public boolean shouldShowInDiscoveryList(BluetoothDevice device) {
        String macAddress = device.getAddress();
        
        // Don't show if already connected
        if (connectedDeviceMacAddresses.contains(macAddress)) {
            Log.d(TAG, "Filtering connected device from discovery: " + macAddress);
            if (connectionCallback != null) {
                connectionCallback.onDiscoveryFiltered(device);
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate connection attempt and handle duplicates
     */
    public boolean canConnect(BluetoothDevice device) {
        String macAddress = device.getAddress();
        
        // Check if device is already connected
        if (connectedDeviceMacAddresses.contains(macAddress)) {
            Log.w(TAG, "Attempted to connect already connected device: " + macAddress);
            if (connectionCallback != null) {
                connectionCallback.onConnectionBlocked(device, "Device already connected");
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Handle connection request with duplicate prevention
     */
    public boolean connectDevice(BluetoothDevice device) {
        String macAddress = device.getAddress();
        
        // Check if we can connect
        if (!canConnect(device)) {
            return false;
        }
        
        // If there's an existing connection for this MAC, disconnect it first
        if (connectedDeviceMacAddresses.contains(macAddress)) {
            Log.i(TAG, "Disconnecting existing connection before reconnecting: " + macAddress);
            disconnectDevice(device);
        }
        
        // Add to connected devices set
        connectedDeviceMacAddresses.add(macAddress);
        deviceConnectionStates.put(macAddress, BluetoothProfile.STATE_CONNECTING);
        
        Log.i(TAG, "Connecting device: " + macAddress);
        
        // Here you would call the actual Bluetooth connection method
        // This depends on your specific automotive Bluetooth implementation
        return performActualConnection(device);
    }
    
    /**
     * Disconnect a device and remove from tracking
     */
    public void disconnectDevice(BluetoothDevice device) {
        String macAddress = device.getAddress();
        
        // Remove from connected devices
        connectedDeviceMacAddresses.remove(macAddress);
        deviceConnectionStates.put(macAddress, BluetoothProfile.STATE_DISCONNECTING);
        
        Log.i(TAG, "Disconnecting device: " + macAddress);
        
        // Perform actual disconnection
        performActualDisconnection(device);
        
        // Update state
        deviceConnectionStates.put(macAddress, BluetoothProfile.STATE_DISCONNECTED);
        
        if (connectionCallback != null) {
            connectionCallback.onDeviceDisconnected(device);
        }
    }
    
    /**
     * Perform the actual Bluetooth connection
     * This method should be implemented based on your specific automotive Bluetooth stack
     */
    private boolean performActualConnection(BluetoothDevice device) {
        // TODO: Implement actual connection logic based on your automotive system
        // This might involve calling specific automotive Bluetooth APIs
        
        // For Android Bluetooth, this might look like:
        // bluetoothSocket = device.createRfcommSocketToServiceRecord(uuid);
        // bluetoothSocket.connect();
        
        // For now, simulate successful connection
        deviceConnectionStates.put(device.getAddress(), BluetoothProfile.STATE_CONNECTED);
        
        if (connectionCallback != null) {
            connectionCallback.onDeviceConnected(device);
        }
        
        return true;
    }
    
    /**
     * Perform the actual Bluetooth disconnection
     */
    private void performActualDisconnection(BluetoothDevice device) {
        // TODO: Implement actual disconnection logic based on your automotive system
        
        // For Android Bluetooth, this might involve:
        // bluetoothSocket.close();
        // Or calling specific profile disconnect methods
    }
    
    /**
     * Get list of currently connected device MAC addresses
     */
    public Set<String> getConnectedDeviceMacAddresses() {
        return new HashSet<>(connectedDeviceMacAddresses);
    }
    
    /**
     * Check if a specific MAC address is connected
     */
    public boolean isDeviceConnected(String macAddress) {
        return connectedDeviceMacAddresses.contains(macAddress);
    }
    
    /**
     * Broadcast receiver to handle Bluetooth events
     */
    private final BroadcastReceiver bluetoothReceiver = new BroadcastReceiver() {
        @Override
        public void onReceive(Context context, Intent intent) {
            String action = intent.getAction();
            BluetoothDevice device = intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE);
            
            if (device == null) return;
            
            String macAddress = device.getAddress();
            
            switch (action) {
                case BluetoothDevice.ACTION_ACL_CONNECTED:
                    Log.d(TAG, "Device connected: " + macAddress);
                    connectedDeviceMacAddresses.add(macAddress);
                    deviceConnectionStates.put(macAddress, BluetoothProfile.STATE_CONNECTED);
                    if (connectionCallback != null) {
                        connectionCallback.onDeviceConnected(device);
                    }
                    break;
                    
                case BluetoothDevice.ACTION_ACL_DISCONNECTED:
                    Log.d(TAG, "Device disconnected: " + macAddress);
                    connectedDeviceMacAddresses.remove(macAddress);
                    deviceConnectionStates.put(macAddress, BluetoothProfile.STATE_DISCONNECTED);
                    if (connectionCallback != null) {
                        connectionCallback.onDeviceDisconnected(device);
                    }
                    break;
                    
                case BluetoothDevice.ACTION_FOUND:
                    // This is where we filter the discovery list
                    if (!shouldShowInDiscoveryList(device)) {
                        // Don't add to discovery list if already connected
                        return;
                    }
                    break;
                    
                case BluetoothDevice.ACTION_BOND_STATE_CHANGED:
                    int bondState = intent.getIntExtra(BluetoothDevice.EXTRA_BOND_STATE, -1);
                    if (bondState == BluetoothDevice.BOND_NONE) {
                        // Device unpaired, remove from connected list
                        connectedDeviceMacAddresses.remove(macAddress);
                        deviceConnectionStates.remove(macAddress);
                    }
                    break;
            }
        }
    };
    
    /**
     * Clean up resources
     */
    public void cleanup() {
        try {
            context.unregisterReceiver(bluetoothReceiver);
        } catch (IllegalArgumentException e) {
            Log.w(TAG, "Receiver not registered", e);
        }
        
        connectedDeviceMacAddresses.clear();
        deviceConnectionStates.clear();
    }
}