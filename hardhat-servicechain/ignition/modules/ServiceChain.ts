import { buildModule } from "@nomicfoundation/hardhat-ignition/modules";

export default buildModule("ServiceChainModule", (m) => {
  const serviceChain = m.contract("ServiceChain");

  return { serviceChain };
});
