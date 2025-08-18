from web3 import Web3
import hashlib
import json
from datetime import datetime
from typing import Dict, Any

class BlockchainService:
    def __init__(self):
        # Connect to blockchain (example: Ethereum)
        self.w3 = Web3(Web3.HTTPProvider('http://localhost:8545'))
        self.contract_address = '0x...'  # Smart contract address
        self.private_key = 'YOUR_PRIVATE_KEY'
        
    def record_product(self, product_data: Dict[str, Any]) -> str:
        """Record product on blockchain for authenticity"""
        # Create product hash
        product_hash = self._create_hash(product_data)
        
        # Create transaction
        transaction = {
            'type': 'product_registration',
            'product_id': product_data['id'],
            'sku': product_data['sku'],
            'manufacturer': product_data['manufacturer'],
            'timestamp': datetime.utcnow().isoformat(),
            'hash': product_hash
        }
        
        # Record on blockchain
        tx_hash = self._send_transaction(transaction)
        
        return tx_hash
    
    def verify_product(self, product_hash: str) -> Dict[str, Any]:
        """Verify product authenticity"""
        # Query blockchain
        result = self._query_blockchain(product_hash)
        
        if result:
            return {
                'authentic': True,
                'registration_date': result['timestamp'],
                'manufacturer': result['manufacturer'],
                'blockchain_proof': result['tx_hash']
            }
        else:
            return {
                'authentic': False,
                'reason': 'Product not found on blockchain'
            }
    
    def record_transaction(self, transaction_data: Dict[str, Any]) -> str:
        """Record payment transaction"""
        tx_hash = self._create_hash(transaction_data)
        
        # Store transaction
        blockchain_tx = {
            'type': 'payment',
            'order_id': transaction_data['order_id'],
            'amount': transaction_data['amount'],
            'method': transaction_data['method'],
            'timestamp': datetime.utcnow().isoformat(),
            'hash': tx_hash
        }
        
        return self._send_transaction(blockchain_tx)
    
    def _create_hash(self, data: Dict) -> str:
        """Create SHA256 hash of data"""
        data_string = json.dumps(data, sort_keys=True)
        return hashlib.sha256(data_string.encode()).hexdigest()
    
    def _send_transaction(self, data: Dict) -> str:
        """Send transaction to blockchain"""
        # Implementation depends on blockchain choice
        # This is a simplified example
        account = self.w3.eth.account.from_key(self.private_key)
        
        # Build transaction
        transaction = {
            'from': account.address,
            'to': self.contract_address,
            'value': 0,
            'gas': 200000,
            'gasPrice': self.w3.toWei('50', 'gwei'),
            'nonce': self.w3.eth.get_transaction_count(account.address),
            'data': self.w3.toHex(text=json.dumps(data))
        }
        
        # Sign and send
        signed = account.sign_transaction(transaction)
        tx_hash = self.w3.eth.send_raw_transaction(signed.rawTransaction)
        
        return tx_hash.hex()
